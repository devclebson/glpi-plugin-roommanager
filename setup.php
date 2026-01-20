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

      // 1. Menu da Grade (Ferramentas)
      $PLUGIN_HOOKS['menu_toadd']['roommanager'] = ['tools' => Booking::class];
      
      // 2. Menu de Configuração (Plugins > Room Manager)
      // Aqui registramos as classes de cadastro
      $PLUGIN_HOOKS['config_page']['roommanager'] = 'front/room.php';
   }
   
   // Registra as classes para o GLPI saber que elas existem
   Plugin::registerClass(Booking::class);
   Plugin::registerClass(Room::class, ['addtabon' => []]); // Adicione 'Entity' no array se quiser aba na entidade
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
            'min' => '10.0.0', // Mantive 10 para compatibilidade, mas funciona no 11
            'max' => '12.0.0'
         ]
      ]
   ];
}

function plugin_roommanager_check_prerequisites() { return true; }
function plugin_roommanager_check_config() { return true; }