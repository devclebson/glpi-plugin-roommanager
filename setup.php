<?php

/**
 * Função de inicialização do plugin
 */
function plugin_init_roommanager() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['roommanager'] = true;

   // Aqui registraremos as classes e o CSS/JS no futuro
}

/**
 * Informações sobre a versão e autor
 */
function plugin_version_roommanager() {
   return [
      'name'           => 'Room Manager',
      'version'        => '1.0.0',
      'author'         => 'Clebson',
      'license'        => 'GPLv2+',
      'homepage'       => '',
      'requirements'   => [
         'glpi' => [
            'min' => '10.0.0'
         ]
      ]
   ];
}

/**
 * Verifica pré-requisitos antes de instalar
 */
function plugin_roommanager_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '10.0.0', '<')) {
      echo "Este plugin requer GLPI >= 10.0.0";
      return false;
   }
   return true;
}