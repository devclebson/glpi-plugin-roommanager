<?php
/**
 * plugins/roommanager/setup.php
 */

define('PLUGIN_ROOMMANAGER_VERSION', '1.0.1');

function plugin_init_roommanager() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['roommanager'] = true;

   if (Session::getLoginUserID()) {
      // APENAS MENU FERRAMENTAS (Para Admins/Técnicos)
      // Removemos o 'helpdesk' para não gerar o item vazio na barra do FormCreator
      $PLUGIN_HOOKS['menu_toadd']['roommanager'] = ['tools' => 'PluginRoommanagerBooking'];
   }

   // Mantemos o registro da classe
   Plugin::registerClass('PluginRoommanagerBooking', [
      'addtab_on' => [] 
   ]);
}

function plugin_version_roommanager() {
   return [
      'name'           => 'Room Manager',
      'version'        => PLUGIN_ROOMMANAGER_VERSION,
      'author'         => 'Grupo SCC',
      'license'        => 'GPLv2+',
      'requirements'   => ['glpi' => ['min' => '10.0.0']]
   ];
}

function plugin_roommanager_check_prerequisites() { return true; }
function plugin_roommanager_check_config() { return true; }