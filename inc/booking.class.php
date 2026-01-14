<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginRoommanagerBooking extends CommonGLPI {

   // Nome que aparecerá na aba do navegador e no título
   static function getTypeName($nb = 0) {
      return "Reserva de Salas";
   }

   // Nome que aparecerá no Menu
   static function getMenuName() {
      return "Reserva de Salas";
   }

   // Definição forçada do link do menu (Corrige o erro 404 do menu)
   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getMenuName();
      // Usa caminho relativo inteligente
      $menu['page']  = '/plugins/roommanager/front/booking.php'; 
      $menu['icon']  = self::getIcon();
      return $menu;
   }

   // A CORREÇÃO DE PERMISSÃO ESTÁ AQUI
   static function canView() {
      // Retorna true sempre. A segurança de "estar logado" 
      // já é feita pelo Session::checkLoginUser() no arquivo front.
      return true; 
   }
   
   static function getIcon() {
      // Ícone do calendário
      return "ti ti-calendar-time";
   }
}