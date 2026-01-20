<?php

use GlpiPlugin\Roommanager\Booking;
use GlpiPlugin\Roommanager\Room;
use GlpiPlugin\Roommanager\Slot;

define('PLUGIN_ROOMMANAGER_VERSION', '1.1.0');

function plugin_init_roommanager() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['roommanager'] = true;

   if (Session::getLoginUserID()) {
      // Adiciona CSS
      $PLUGIN_HOOKS['add_css']['roommanager'] = 'css/styles.css';

      // 1. Menu para ADMINS (Interface Padrão -> Ferramentas)
      $PLUGIN_HOOKS['menu_toadd']['roommanager'] = ['tools' => Booking::class];
      
      // 2. Menu para SELF-SERVICE (Interface Simplificada)  <-- NOVO!
      // Isso cria um link no topo da página do usuário comum
      $PLUGIN_HOOKS['helpdesk_menu']['roommanager'] = 'front/booking.php';

      // 3. Menu de Configuração (Plugins)
      $PLUGIN_HOOKS['config_page']['roommanager'] = 'front/room.php';
   }
   
   Plugin::registerClass(Booking::class);
   Plugin::registerClass(Room::class, ['addtabon' => []]);
   Plugin::registerClass(Slot::class);
}

function plugin_version_roommanager() {
   return [
      'name'           => 'Room Manager',
      'version'        => PLUGIN_ROOMMANAGER_VERSION,
      'author'         => 'Grupo SCC',
      'license'        => 'GPLv2+',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0',
            'max' => '12.0.0'
         ]
      ]
   ];
}

function plugin_roommanager_check_prerequisites() { return true; }
function plugin_roommanager_check_config() { return true; }