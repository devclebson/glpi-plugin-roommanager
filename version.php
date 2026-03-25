<?php
// plugins/roommanager/version.php
define('PLUGIN_ROOMMANAGER_VERSION', '1.3.1');

$PLUGIN_HOOKS['version_plugin_roommanager'] = [
   'name'           => 'Room Manager',
   'version'        => PLUGIN_ROOMMANAGER_VERSION,
   'author'         => 'Grupo SCC',
   'license'        => 'GPLv2+',
   'requirements'   => [
      'glpi' => [
         'min' => '10.0.0'
      ]
   ]
];