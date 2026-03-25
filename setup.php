<?php

use GlpiPlugin\Roommanager\Booking;
use GlpiPlugin\Roommanager\Room;
use GlpiPlugin\Roommanager\Config;

define('PLUGIN_ROOMMANAGER_VERSION', '1.3.1');

function plugin_init_roommanager() {
   global $PLUGIN_HOOKS;

   // Registro usando o caminho completo (Namespace)
   Plugin::registerClass('GlpiPlugin\Roommanager\Booking', ['addtabon' => 'Booking']);
   Plugin::registerClass('GlpiPlugin\Roommanager\Room');
   Plugin::registerClass('GlpiPlugin\Roommanager\Config');

   $PLUGIN_HOOKS['csrf_compliant']['roommanager'] = true;

   if (Session::getLoginUserID()) {
      $PLUGIN_HOOKS['add_css']['roommanager'] = 'css/styles.css';

      // Importante: Referencie a classe com o namespace completo no menu
      $PLUGIN_HOOKS['menu_toadd']['roommanager'] = [
          'tools' => 'GlpiPlugin\Roommanager\Booking'
      ];
      
      $PLUGIN_HOOKS['config_page']['roommanager'] = 'front/config.php';
   }
}

function plugin_version_roommanager() {
   return [
      'name'           => 'Room Manager',
      'version'        => PLUGIN_ROOMMANAGER_VERSION,
      'author'         => 'Grupo SCC',
      'license'        => 'GPLv2+',
      'requirements'   => [
         'glpi' => ['min' => '10.0.0']
      ]
   ];
}

function plugin_roommanager_check_prerequisites() { return true; }
function plugin_roommanager_check_config() { return true; }