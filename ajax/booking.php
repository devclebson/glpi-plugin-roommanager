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

    // --- NOVA AÇÃO: RESERVA POR INTERVALO E RECORRÊNCIA ---
    if ($action === 'add_range') {
        $room_id    = (int) $_POST['room_id'];
        $start_time = $_POST['start_time']; // ex: "09:00:00"
        $end_time   = $_POST['end_time'];   // ex: "11:00:00"
        $base_date  = $_POST['date'];
        $name       = $_POST['event_name'];
        
        // Recorrência
        $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == 'on';
        $weeks        = $is_recurring ? (int)$_POST['recur_weeks'] : 0;
        
        // 1. Identificar quais Slot IDs correspondem ao intervalo de horas
        // A query busca slots que COMEÇAM >= hora inicio E TERMINAM <= hora fim
        // Isso permite que o usuário selecione "09:00 as 11:00" e o sistema pegue slots 09:00, 09:30, 10:00, 10:30.
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

        if (empty($target_slots)) sendError("Nenhum horário válido encontrado neste intervalo (verifique se a hora fim é maior que início).");

        // 2. Calcular as datas (Recorrência)
        $dates_to_book = [$base_date];
        if ($is_recurring && $weeks > 0) {
            for ($i = 1; $i <= $weeks; $i++) {
                // Adiciona X semanas à data base
                $dates_to_book[] = date('Y-m-d', strtotime("+$i week", strtotime($base_date)));
            }
        }

        // 3. Validação de Conflito em Massa
        // Verifica se QUALQUER uma das datas + slots já tem reserva
        $conflicts = $DB->request([
            'FROM' => 'glpi_plugin_roommanager_bookings',
            'WHERE' => [
                'plugin_roommanager_rooms_id' => $room_id,
                'plugin_roommanager_slots_id' => $target_slots,
                'date' => $dates_to_book
            ]
        ])->count();

        if ($conflicts > 0) {
            sendError("Conflito detectado! Em uma das datas ou horários selecionados (recorrência) já existe uma reserva.");
        }

        // 4. Inserir Tudo
        $group_id = uniqid('rec_'); // ID único para agrupar essa série
        
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

    // --- DELETAR (INTELIGENTE) ---
    elseif ($action === 'delete') {
        $booking_id = (int) $_POST['booking_id'];
        $delete_series = isset($_POST['delete_series']) && $_POST['delete_series'] == '1';

        // Busca informações da reserva para saber o group_id
        $booking = $DB->request([
            'FROM' => 'glpi_plugin_roommanager_bookings',
            'WHERE' => ['id' => $booking_id]
        ])->current();

        if (!$booking) sendError("Reserva não encontrada.");

        // Permissão
        if (!Session::haveRight("config", UPDATE) && $booking['users_id'] != $my_uid) {
            sendError("Sem permissão.");
        }

        if ($delete_series && !empty($booking['group_id'])) {
            // Apaga TODAS com o mesmo group_id (que sejam da data atual para frente, opcionalmente)
            $DB->delete('glpi_plugin_roommanager_bookings', ['group_id' => $booking['group_id']]);
        } else {
            // Apaga só esta
            $DB->delete('glpi_plugin_roommanager_bookings', ['id' => $booking_id]);
        }

        echo json_encode(['success' => true]);
    }

} catch (\Throwable $e) {
    sendError('Erro: ' . $e->getMessage());
}