<?php
// plugins/roommanager/ajax/booking.php

ob_start();
include ("../../../inc/includes.php");
ob_end_clean();

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();
$user_id = Session::getLoginUserID();
global $DB;

$action = $_POST['action'] ?? 'add';

// --- CANCELAR ---
if ($action === 'delete') {
    if (!isset($_POST['booking_id'])) exit;
    $booking_id = (int) $_POST['booking_id'];
    
    // Verifica dono
    $query = "SELECT id FROM glpi_plugin_roommanager_bookings WHERE id = $booking_id AND users_id = $user_id";
    $result = $DB->query($query);
    if ($DB->numrows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
        exit;
    }
    
    if ($DB->query("DELETE FROM glpi_plugin_roommanager_bookings WHERE id = $booking_id")) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $DB->error()]);
    }
    exit;
}

// --- ADICIONAR ---
if ($action === 'add') {
    $room_id = (int) $_POST['room_id'];
    $slot_id = (int) $_POST['slot_id'];
    $date    = $_POST['date'];
    // Sanitiza o título do evento (remove tags HTML perigosas)
    $event_name = isset($_POST['event_name']) ? Html::cleanInputText($_POST['event_name']) : 'Reserva'; 

    // Verifica duplicidade
    $query = "SELECT count(*) AS total FROM glpi_plugin_roommanager_bookings 
              WHERE plugin_roommanager_rooms_id = $room_id 
                AND plugin_roommanager_slots_id = $slot_id 
                AND date = '$date'";
    $result = $DB->query($query);
    if ($DB->result($result, 0, 'total') > 0) {
        echo json_encode(['success' => false, 'message' => 'Horário já reservado por outro usuário.']);
        exit;
    }

    // Insere com o Nome do Evento
    $stmt = $DB->prepare("INSERT INTO glpi_plugin_roommanager_bookings 
                          (plugin_roommanager_rooms_id, plugin_roommanager_slots_id, users_id, date, name, date_creation) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
    
    if ($stmt->bind_param("iiiss", $room_id, $slot_id, $user_id, $date, $event_name) && $stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $DB->error()]);
    }
    exit;
}