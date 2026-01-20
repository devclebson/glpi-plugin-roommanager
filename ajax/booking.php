<?php
// plugins/roommanager/ajax/booking.php
include ("../../../inc/includes.php");
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

function sendError($msg) { echo json_encode(['success' => false, 'message' => $msg]); exit; }

try {
    Session::checkLoginUser();
    if (!empty($_POST['action']) && $_POST['action'] != 'get_my_bookings') {
        if (!Session::validateCSRF($_POST)) sendError('Erro de Segurança (CSRF).');
    }

    global $DB;
    $action = $_POST['action'] ?? '';
    $my_uid = Session::getLoginUserID();

    // LISTAR RESERVAS (Agora retorna start_time e end_time reais do banco)
    if ($action === 'get_my_bookings') {
        $today = date('Y-m-d');
        
        $iterator = $DB->request([
            'SELECT' => [
                'b.id', 'b.date', 'b.group_id', 'b.name as event_title', 'b.start_time', 'b.end_time',
                'r.name AS room_name'
            ],
            'FROM'   => 'glpi_plugin_roommanager_bookings AS b',
            'LEFT JOIN' => [
                'glpi_plugin_roommanager_rooms AS r' => ['ON' => ['b', 'plugin_roommanager_rooms_id', 'r', 'id']]
            ],
            'WHERE'  => [
                'b.users_id' => $my_uid,
                'b.date'     => ['>=', $today]
            ],
            'ORDER'  => ['b.date ASC', 'b.start_time ASC']
        ]);

        $bookings = [];
        foreach($iterator as $row) {
            $roomName = $row['room_name'] ?? '<span class="text-danger">(Sala Excluída)</span>';
            // Formata hora (remove segundos)
            $timeLabel = substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5);

            $bookings[] = [
                'id'             => $row['id'],
                'date_formatted' => date('d/m/Y', strtotime($row['date'])),
                'room'           => $roomName,
                'title'          => $row['event_title'] ?: 'Sem título',
                'time'           => $timeLabel,
                'is_series'      => !empty($row['group_id'])
            ];
        }
        echo json_encode(['success' => true, 'bookings' => $bookings]);
        exit;
    }

    // ADICIONAR RESERVA (Timeline Livre)
    elseif ($action === 'add_range') {
        $room_id    = (int) $_POST['room_id'];
        $start_time = $_POST['start_time']; // Ex: "08:15"
        $end_time   = $_POST['end_time'];   // Ex: "09:30"
        $base_date  = $_POST['date'];
        $name       = $_POST['event_name'];
        
        // Validação básica
        if ($base_date < date('Y-m-d')) sendError("Data no passado.");
        if ($start_time >= $end_time) sendError("Hora fim deve ser maior que início.");

        $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == 'on';
        $weeks        = $is_recurring ? (int)$_POST['recur_weeks'] : 0;
        
        // Calcula Datas
        $dates_to_book = [$base_date];
        if ($is_recurring && $weeks > 0) {
            for ($i = 1; $i <= $weeks; $i++) {
                $dates_to_book[] = date('Y-m-d', strtotime("+$i week", strtotime($base_date)));
            }
        }

        // --- VALIDAÇÃO DE CONFLITO POR TEMPO (TIMELINE) ---
        // Verifica se existe alguma reserva que comece antes do meu fim E termine depois do meu início
        $conflictQuery = [
            'FROM' => 'glpi_plugin_roommanager_bookings',
            'WHERE' => [
                'plugin_roommanager_rooms_id' => $room_id,
                'date' => $dates_to_book,
                'start_time' => ['<', $end_time],
                'end_time'   => ['>', $start_time]
            ]
        ];

        if ($DB->request($conflictQuery)->count() > 0) {
            sendError("Conflito de horário! Já existe uma reserva neste intervalo.");
        }

        // Insere
        $group_id = uniqid('rec_'); 
        $stmt = $DB->prepare("INSERT INTO glpi_plugin_roommanager_bookings 
            (plugin_roommanager_rooms_id, users_id, date, start_time, end_time, name, date_creation, group_id) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");

        foreach ($dates_to_book as $d) {
            // Nota: slot_id agora vai NULL ou 0, pois é reserva por tempo
            $stmt->bind_param('iisssss', $room_id, $my_uid, $d, $start_time, $end_time, $name, $group_id);
            $stmt->execute();
        }

        echo json_encode(['success' => true]);
    }

    elseif ($action === 'delete') {
        $booking_id = (int) $_POST['booking_id'];
        $delete_series = isset($_POST['delete_series']) && $_POST['delete_series'] == '1';

        $booking = $DB->request([
            'FROM' => 'glpi_plugin_roommanager_bookings',
            'WHERE' => ['id' => $booking_id]
        ])->current();

        if (!$booking) sendError("Reserva não encontrada.");
        if (!Session::haveRight("config", UPDATE) && $booking['users_id'] != $my_uid) sendError("Sem permissão.");

        if ($delete_series && !empty($booking['group_id'])) {
            $DB->delete('glpi_plugin_roommanager_bookings', ['group_id' => $booking['group_id']]);
        } else {
            $DB->delete('glpi_plugin_roommanager_bookings', ['id' => $booking_id]);
        }
        echo json_encode(['success' => true]);
    }

} catch (\Throwable $e) {
    sendError('Erro no sistema: ' . $e->getMessage());
}