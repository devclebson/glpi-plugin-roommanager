<?php
// plugins/roommanager/inc/booking.class.php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginRoommanagerBooking extends CommonGLPI {

   static function getTypeName($nb = 0) {
      return "Reserva de Salas";
   }

   // Nome no Menu
   static function getMenuName() {
      return "Reserva de Salas";
   }

   // Configuração do Menu (Ícone e Link)
   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = '/plugins/roommanager/front/booking.php'; 
      $menu['icon']  = 'ti ti-calendar-time'; 
      return $menu;
   }

   // Permissão de Visualização: LIBERADO GERAL
   // Retornar true aqui é crucial para o Self-Service ver
   static function canView() {
      return true; 
   }

   // Define o ícone para a interface simplificada (importante para GLPI 10)
   static function getIcon() {
      return "ti ti-calendar-time";
   }
}