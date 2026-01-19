<?php
define('PLUGIN_ROOMMANAGER_VERSION', '1.1.0');

// Array de informações básicas
$PLUGIN_HOOKS['version_plugin_roommanager'] = [
   'name'           => 'Room Manager',
   'version'        => PLUGIN_ROOMMANAGER_VERSION,
   'author'         => 'Grupo SCC',
   'license'        => 'GPLv2+',
   'homepage'       => '',
   'requirements'   => [
      'glpi' => [
         'min' => '11.0.0', // Exige GLPI 11
         'max' => '12.0.0'
      ]
   ]
];