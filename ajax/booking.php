<?php
// plugins/roommanager/ajax/booking.php
include ("../../../inc/includes.php");
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

function sendError($msg) { echo json_encode(['success' => false, 'message' => $msg]); exit; }

try {
    // Verifica sessão
    Session::checkLoginUser();
    
    // Ignora CSRF apenas para leitura (get_my_bookings), valida para gravação
    if (!empty($_POST['action']) && $_POST['action'] != 'get_my_bookings') {
        if (!Session::validateCSRF($_POST)) sendError('Erro de Segurança (CSRF).');
    }

    global $DB;
    $action = $_POST['action'] ?? '';
    $my_uid = Session::getLoginUserID();

    // ==========================================
    // 1. AÇÃO: LISTAR MINHAS RESERVAS (VERSÃO BLINDADA)
    // ==========================================
    if ($action === 'get_my_bookings') {
        $today = date('Y-m-d');
        
        // Query "Blindada": Usamos nomes completos de tabelas e sintaxe FKEY do GLPI
        // Isso evita erros de interpretação do motor de banco de dados do GLPI
        $iterator = $DB->request([
            'SELECT' => [
                'glpi_plugin_roommanager_bookings.id', 
                'glpi_plugin_roommanager_bookings.date', 
                'glpi_plugin_roommanager_bookings.group_id', 
                'glpi_plugin_roommanager_bookings.name as event_title',
                'glpi_plugin_roommanager_rooms.name AS room_name', 
                'glpi_plugin_roommanager_slots.start_time', 
                'glpi_plugin_roommanager_slots.end_time'
            ],
            'FROM'   => 'glpi_plugin_roommanager_bookings',
            'LEFT JOIN' => [
                'glpi_plugin_roommanager_rooms' => [
                    'FKEY' => [
                        'glpi_plugin_roommanager_bookings' => 'plugin_roommanager_rooms_id',
                        'glpi_plugin_roommanager_rooms'    => 'id'
                    ]
                ],
                'glpi_plugin_roommanager_slots' => [
                    'FKEY' => [
                        'glpi_plugin_roommanager_bookings' => 'plugin_roommanager_slots_id',
                        'glpi_plugin_roommanager_slots'    => 'id'
                    ]
                ]
            ],
            'WHERE'  => [
                'glpi_plugin_roommanager_bookings.users_id' => $my_uid,
                'glpi_plugin_roommanager_bookings.date'     => ['>=', $today]
            ],
            'ORDER'  => [
                'glpi_plugin_roommanager_bookings.date ASC', 
                'glpi_plugin_roommanager_slots.start_time ASC'
            ]
        ]);

        $bookings = [];
        foreach($iterator as $row) {
            // Tratamento visual caso sala/slot tenha sido deletado (Fallback)
            $roomName = $row['room_name'] ?? '<span class="text-danger">(Sala Excluída)</span>';
            
            if (!empty($row['start_time']) && !empty($row['end_time'])) {
                $timeLabel = substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5);
            } else {
                $timeLabel = '<span class="text-danger">--:--</span>';
            }

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

    // ==========================================
    // 2. AÇÃO: ADICIONAR RESERVA
    // ==========================================
    elseif ($action === 'add_range') {
        $room_id    = (int) $_POST['room_id'];
        $start_time = $_POST['start_time']; 
        $end_time   = $_POST['end_time'];   
        $base_date  = $_POST['date'];
        $name       = $_POST['event_name'];
        
        // Validação de Passado
        $hoje = date('Y-m-d');
        if ($base_date < $hoje) {
            sendError("Não é permitido agendar em datas passadas.");
        }

        $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == 'on';
        $weeks        = $is_recurring ? (int)$_POST['recur_weeks'] : 0;
        
        // Busca Slots
        $slot_iterator = $DB->request([
            'FROM' => 'glpi_plugin_roommanager_slots',
            'WHERE' => [
                'start_time' => ['>=', $start_time],
                'end_time'   => ['<=', $end_time],
                'is_active'  => 1
            ]
        ]);
        
        $target_slots = [];
        foreach($slot_iterator as $slot) {
            $target_slots[] = $slot['id'];
        }

        if (empty($target_slots)) sendError("Nenhum horário válido encontrado neste intervalo.");

        // Calcula Datas
        $dates_to_book = [$base_date];
        if ($is_recurring && $weeks > 0) {
            for ($i = 1; $i <= $weeks; $i++) {
                $dates_to_book[] = date('Y-m-d', strtotime("+$i week", strtotime($base_date)));
            }
        }

        // Verifica Conflitos
        $conflicts = $DB->request([
            'FROM' => 'glpi_plugin_roommanager_bookings',
            'WHERE' => [
                'plugin_roommanager_rooms_id' => $room_id,
                'plugin_roommanager_slots_id' => $target_slots,
                'date' => $dates_to_book
            ]
        ])->count();

        if ($conflicts > 0) {
            sendError("Desculpe, conflito detectado! Alguém acabou de reservar um desses horários.");
        }

        // Insere
        $group_id = uniqid('rec_'); 
        $stmt = $DB->prepare("INSERT INTO glpi_plugin_roommanager_bookings 
            (plugin_roommanager_rooms_id, plugin_roommanager_slots_id, users_id, date, name, date_creation, group_id) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)");

        foreach ($dates_to_book as $d) {
            foreach ($target_slots as $slot_id) {
                $stmt->bind_param('iiisss', $room_id, $slot_id, $my_uid, $d, $name, $group_id);
                $stmt->execute();
            }
        }

        echo json_encode(['success' => true]);
    }

    // ==========================================
    // 3. AÇÃO: DELETAR RESERVA
    // ==========================================
    elseif ($action === 'delete') {
        $booking_id = (int) $_POST['booking_id'];
        $delete_series = isset($_POST['delete_series']) && $_POST['delete_series'] == '1';

        $booking = $DB->request([
            'FROM' => 'glpi_plugin_roommanager_bookings',
            'WHERE' => ['id' => $booking_id]
        ])->current();

        if (!$booking) sendError("Reserva não encontrada.");

        if (!Session::haveRight("config", UPDATE) && $booking['users_id'] != $my_uid) {
            sendError("Sem permissão.");
        }

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