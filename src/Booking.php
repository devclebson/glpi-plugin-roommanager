<?php

namespace GlpiPlugin\Roommanager;

use CommonGLPI;
use Session;
use Html;
use Glpi\Application\View\TemplateRenderer;

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

   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/roommanager/front/booking.php';
      $menu['icon']  = self::getIcon();
      return $menu;
   }

   /**
    * Função Principal: Busca dados e Renderiza o Template Twig
    */
   public function displayGrid() {
      global $DB, $CFG_GLPI;

      // 1. Captura filtros da URL ou usa padrão
      $selected_date = $_GET['date'] ?? date('Y-m-d');
      $selected_loc  = isset($_GET['location']) ? (int)$_GET['location'] : 0;
      $my_uid        = Session::getLoginUserID();

      // 2. Busca Locais (Filtrando por "Grupo SCC")
      $locations = [];
      $iterator = $DB->request(['FROM' => 'glpi_locations', 'ORDER' => 'completename']);
      foreach ($iterator as $loc) {
         if (strpos($loc['completename'], 'Grupo SCC') === 0) {
            $locations[$loc['id']] = $loc['completename'];
         }
      }

      // Define local padrão se não escolhido (Tenta Florianópolis)
      if ($selected_loc === 0 && count($locations) > 0) {
         foreach($locations as $id => $name) {
            if (stripos($name, 'Florianópolis') !== false) {
               $selected_loc = $id;
               break;
            }
         }
         if ($selected_loc === 0) $selected_loc = array_key_first($locations);
      }

      // 3. Busca Salas do local selecionado
      $rooms = [];
      $where_rooms = ['is_active' => 1, 'is_deleted' => 0];
      if ($selected_loc > 0) {
         $where_rooms['locations_id'] = $selected_loc;
      }
      $iter = $DB->request(['FROM' => 'glpi_plugin_roommanager_rooms', 'WHERE' => $where_rooms]);
      foreach ($iter as $item) { $rooms[] = $item; }

      // 4. Busca Horários (Slots)
      $slots = [];
      $iter = $DB->request(['FROM' => 'glpi_plugin_roommanager_slots', 'ORDER' => 'start_time ASC']);
      foreach ($iter as $item) { 
          // Formata hora para ficar bonitinho (08:00)
          $item['formatted_start'] = substr($item['start_time'], 0, 5);
          $item['formatted_end']   = substr($item['end_time'], 0, 5);
          $slots[] = $item; 
      }

      // 5. Busca Reservas Existentes na Data
      $bookings_map = [];
      $iter = $DB->request([
         'SELECT' => ['glpi_plugin_roommanager_bookings.*', 'glpi_users.name AS username'],
         'FROM'   => 'glpi_plugin_roommanager_bookings',
         'LEFT JOIN' => ['glpi_users' => ['FKEY' => ['glpi_plugin_roommanager_bookings' => 'users_id', 'glpi_users' => 'id']]],
         'WHERE'  => ['date' => $selected_date]
      ]);
      
      foreach ($iter as $b) {
         $bookings_map[$b['plugin_roommanager_rooms_id']][$b['plugin_roommanager_slots_id']] = $b;
      }

      // 6. Preparar dados para o Template
      $params = [
         'locations'     => $locations,
         'selected_loc'  => $selected_loc,
         'selected_date' => $selected_date,
         'rooms'         => $rooms,
         'slots'         => $slots,
         'bookings_map'  => $bookings_map,
         'current_uid'   => $my_uid,
         'root_doc'      => $CFG_GLPI['root_doc'], // Para caminhos de AJAX/CSS
         'is_past_date'  => ($selected_date < date('Y-m-d'))
      ];

      // 7. RENDERIZA O TEMPLATE TWIG
      // @roommanager refere-se à pasta plugins/roommanager/templates/
      TemplateRenderer::getInstance()->display('@roommanager/booking_grid.html.twig', $params);
   }
}