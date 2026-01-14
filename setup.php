<?php
/**
 * plugins/roommanager/setup.php
 */

define('PLUGIN_ROOMMANAGER_VERSION', '1.0.0');

// Função de Inicialização (Roda a cada carregamento de página)
function plugin_init_roommanager() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['roommanager'] = true;

   // --- CORREÇÃO DO MENU ---
   // Verifica se o usuário está logado
   if (Session::getLoginUserID()) {
      // Adiciona o link no menu "Ferramentas" (tools)
      // Aponta para a classe PluginRoommanagerBooking que tem o método getMenuContent()
      $PLUGIN_HOOKS['menu_toadd']['roommanager'] = ['tools' => 'PluginRoommanagerBooking'];
   }

   // Registra a classe para o GLPI saber que ela existe
   Plugin::registerClass('PluginRoommanagerBooking', [
      'addtab_on' => [] // Removemos 'Central' para evitar abas desnecessárias na home
   ]);
}

// Informações sobre a versão
function plugin_version_roommanager() {
   return [
      'name'           => 'Room Manager (Reserva de Salas)',
      'version'        => PLUGIN_ROOMMANAGER_VERSION,
      'author'         => 'Grupo SCC',
      'license'        => 'GPLv2+',
      'homepage'       => '',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0'
         ]
      ]
   ];
}

// Checagem de pré-requisitos
function plugin_roommanager_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '10.0.0', 'lt')) {
      echo "Este plugin requer GLPI >= 10.0.0";
      return false;
   }
   return true;
}

// Checagem de configuração
function plugin_roommanager_check_config() {
   return true;
}