<?php
// plugins/roommanager/ajax/booking.php
include ("../../../inc/includes.php");
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

function sendError($msg) { echo json_encode(['success' => false, 'message' => $msg]); exit; }

try {
    Session::checkLoginUser();
    if (!Session::validateCSRF($_POST)) sendError('Erro de Segurança (CSRF).');

    global $DB;
    $action = $_POST['action'] ?? '';
    $my_uid = Session::getLoginUserID();

    // --- AÇÃO: RESERVA POR INTERVALO E RECORRÊNCIA ---
    if ($action === 'add_range') {
        $room_id    = (int) $_POST['room_id'];
        $start_time = $_POST['start_time']; 
        $end_time   = $_POST['end_time'];   
        $base_date  = $_POST['date'];
        $name       = $_POST['event_name'];
        
        // Recorrência
        $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == 'on';
        $weeks        = $is_recurring ? (int)$_POST['recur_weeks'] : 0;
        
        // 1. Identificar quais Slot IDs correspondem ao intervalo
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

        // 2. Calcular as datas
        $dates_to_book = [$base_date];
        if ($is_recurring && $weeks > 0) {
            for ($i = 1; $i <= $weeks; $i++) {
                $dates_to_book[] = date('Y-m-d', strtotime("+$i week", strtotime($base_date)));
            }
        }

        // 3. Validação de Conflito em Massa (BLINDAGEM DUPLA)
        // Fazemos uma verificação "atômica" antes de inserir
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

        // 4. Inserir Tudo
        $group_id = uniqid('rec_'); 
        
        $stmt = $DB->prepare("INSERT INTO glpi_plugin_roommanager_bookings 
            (plugin_roommanager_rooms_id, plugin_roommanager_slots_id, users_id, date, name, date_creation, group_id) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)");

        foreach ($dates_to_book as $d) {
            foreach ($target_slots as $slot_id) {
                // Revalidação final slot a slot (opcional, mas ultra seguro)
                // Se preferir performance, confie no count() acima.
                $stmt->bind_param('iiisss', $room_id, $slot_id, $my_uid, $d, $name, $group_id);
                $stmt->execute();
            }
        }

        echo json_encode(['success' => true]);
    }

    // --- DELETAR (INTELIGENTE) ---
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
    sendError('Erro: ' . $e->getMessage());
}