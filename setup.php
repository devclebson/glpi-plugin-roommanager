<?php

// IMPORTANTE: Namespace para GLPI 11
use GlpiPlugin\Roommanager\Booking;

define('PLUGIN_ROOMMANAGER_VERSION', '1.1.0');

function plugin_init_roommanager() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['roommanager'] = true;

   if (Session::getLoginUserID()) {
      // Aponta para a classe com Namespace
      $PLUGIN_HOOKS['menu_toadd']['roommanager'] = ['tools' => Booking::class];
   }
   
   // Registra a classe
   Plugin::registerClass(Booking::class);
}

function plugin_version_roommanager() {
   return [
      'name'           => 'Room Manager',
      'version'        => PLUGIN_ROOMMANAGER_VERSION,
      'author'         => 'Grupo SCC',
      'license'        => 'GPLv2+',
      'requirements'   => [
         'glpi' => [
            'min' => '11.0.0'
         ]
      ]
   ];
}

function plugin_roommanager_check_prerequisites() { return true; }
function plugin_roommanager_check_config() { return true; }