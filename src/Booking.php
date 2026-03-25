<?php

namespace GlpiPlugin\Roommanager;

use CommonGLPI;
use Session;
use Html;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Roommanager\Config;

/**
 * Classe Booking - Responsável pela lógica da grade de visualização.
 * Nome do arquivo deve ser obrigatoriamente: src/Booking.php
 */
class Booking extends CommonGLPI {

   static function getTypeName($nb = 0) { 
      return "Reserva de Salas"; 
   }

   static function getMenuName() { 
      return "Reserva de Salas"; 
   }

   static function getIcon() { 
      return "ti ti-calendar-time"; 
   }

   static function canView(): bool { 
      return (bool)Session::getLoginUserID(); 
   }

   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/roommanager/front/booking.php';
      $menu['icon']  = self::getIcon();
      return $menu;
   }

   /**
    * Renderiza a grade de reservas utilizando os parâmetros configurados no banco.
    */
   public function displayGrid() {
      global $DB, $CFG_GLPI;

      // 1. CARREGAR CONFIGURAÇÕES DINÂMICAS
      $configObj = new Config();
      if (!$configObj->getFromDB(1)) {
         $configObj->getEmpty();
         $configObj->fields['day_start_hour'] = 8;
         $configObj->fields['day_end_hour']   = 19;
         $configObj->fields['slot_interval']  = 15;
      }

      $day_start_hour = $configObj->fields['day_start_hour'];
      $day_end_hour   = $configObj->fields['day_end_hour'];
      $slot_interval  = $configObj->fields['slot_interval'];
      
      $day_start_min = $day_start_hour * 60; 
      $day_end_min   = $day_end_hour * 60;
      $total_day_minutes = $day_end_min - $day_start_min;

      $selected_date = $_GET['date'] ?? date('Y-m-d');
      $selected_loc  = isset($_GET['location']) ? (int)$_GET['location'] : 0;
      $my_uid        = Session::getLoginUserID();

      // 2. BUSCAR LOCAIS (Filtrados pelo Grupo SCC)
      $locations = [];
      $iterator = $DB->request(['FROM' => 'glpi_locations', 'ORDER' => 'completename']);
      foreach ($iterator as $loc) {
         if (strpos($loc['completename'], 'Grupo SCC') === 0) {
            $locations[$loc['id']] = $loc['completename'];
         }
      }
      if ($selected_loc === 0 && count($locations) > 0) {
         $selected_loc = array_key_first($locations);
      }

      // 3. BUSCAR SALAS
      $rooms = [];
      $where_rooms = ['is_active' => 1, 'is_deleted' => 0];
      if ($selected_loc > 0) {
         $where_rooms['locations_id'] = $selected_loc;
      }
      $iter = $DB->request(['FROM' => 'glpi_plugin_roommanager_rooms', 'WHERE' => $where_rooms]);
      foreach ($iter as $item) { 
         $rooms[] = $item; 
      }

      // 4. BUSCAR RESERVAS (CORREÇÃO: Uso de ON em vez de FKEY para evitar erro SQL)
      $bookings_map = [];
      $iter = $DB->request([
         'SELECT' => ['b.*', 'u.name AS username'],
         'FROM'   => 'glpi_plugin_roommanager_bookings AS b',
         'LEFT JOIN' => [
            'glpi_users AS u' => [
               'ON' => [
                  'b' => 'users_id',
                  'u' => 'id'
               ]
            ]
         ],
         'WHERE'  => ['b.date' => $selected_date]
      ]);
      
      foreach ($iter as $b) {
         $room_id = $b['plugin_roommanager_rooms_id'];
         
         $b_start_parts = explode(':', $b['start_time']);
         $b_end_parts   = explode(':', $b['end_time']);
         
         $b_start_min = ($b_start_parts[0] * 60) + $b_start_parts[1];
         $b_end_min   = ($b_end_parts[0] * 60) + $b_end_parts[1];

         // Ajuste visual para respeitar os limites da grade configurada
         if ($b_start_min < $day_start_min) $b_start_min = $day_start_min;
         if ($b_end_min > $day_end_min) $b_end_min = $day_end_min;

         $duration = $b_end_min - $b_start_min;
         $offset   = $b_start_min - $day_start_min;

         $b['css_top']    = ($offset / $total_day_minutes) * 100;
         $b['css_height'] = ($duration / $total_day_minutes) * 100;
         $b['format_time'] = substr($b['start_time'], 0, 5) . ' - ' . substr($b['end_time'], 0, 5);

         $bookings_map[$room_id][] = $b;
      }

      // 5. GERAR OPÇÕES DE HORÁRIO (Conforme Slot Interval configurado)
      $time_options = [];
      for ($i = $day_start_min; $i <= $day_end_min; $i += $slot_interval) { 
          $h = floor($i / 60); 
          $m = $i % 60;
          $time_options[] = sprintf('%02d:%02d', $h, $m);
      }

      // 6. RENDERIZAR TEMPLATE
      TemplateRenderer::getInstance()->display('@roommanager/booking_grid.html.twig', [
         'locations'      => $locations,
         'selected_loc'   => $selected_loc,
         'selected_date'  => $selected_date,
         'rooms'          => $rooms,
         'bookings_map'   => $bookings_map,
         'time_options'   => $time_options,
         'day_start'      => $day_start_hour,
         'day_end'        => $day_end_hour,
         'current_uid'    => $my_uid,
         'root_doc'       => $CFG_GLPI['root_doc'],
         'is_past_date'   => ($selected_date < date('Y-m-d'))
      ]);
   }
}