<?php
/**
 * plugins/roommanager/hook.php
 */

use Glpi\Plugin\Hooks;

function plugin_roommanager_install() {
   global $DB;

   $migration = new \Migration(PLUGIN_ROOMMANAGER_VERSION);

   // 1. Tabela de Salas
   if (!$DB->tableExists('glpi_plugin_roommanager_rooms')) {
      // MUDANÇAS: id unsigned, locations_id unsigned, timestamp em vez de datetime
      $query = "CREATE TABLE `glpi_plugin_roommanager_rooms` (
         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `locations_id` int(10) unsigned NOT NULL DEFAULT '0',
         `capacity` int(11) NOT NULL DEFAULT '0',
         `is_active` tinyint(1) NOT NULL DEFAULT '1',
         `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
         `comment` text,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `locations_id` (`locations_id`),
         KEY `is_deleted` (`is_deleted`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $migration->addPostQuery($query);
   }

   // 2. Tabela de Slots (Horários)
   if (!$DB->tableExists('glpi_plugin_roommanager_slots')) {
      // MUDANÇAS: id unsigned
      $query = "CREATE TABLE `glpi_plugin_roommanager_slots` (
         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `start_time` time NOT NULL,
         `end_time` time NOT NULL,
         `is_active` tinyint(1) NOT NULL DEFAULT '1',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $migration->addPostQuery($query);
      
      $migration->addPostQuery("INSERT INTO `glpi_plugin_roommanager_slots` (name, start_time, end_time) VALUES 
                  ('08:00 - 09:00', '08:00:00', '09:00:00'),
                  ('09:00 - 10:00', '09:00:00', '10:00:00'),
                  ('10:00 - 11:00', '10:00:00', '11:00:00'),
                  ('11:00 - 12:00', '11:00:00', '12:00:00'),
                  ('13:00 - 14:00', '13:00:00', '14:00:00'),
                  ('14:00 - 15:00', '14:00:00', '15:00:00'),
                  ('15:00 - 16:00', '15:00:00', '16:00:00'),
                  ('16:00 - 17:00', '16:00:00', '17:00:00'),
                  ('17:00 - 18:00', '17:00:00', '18:00:00')");
   }

   // 3. Tabela de Reservas
   if (!$DB->tableExists('glpi_plugin_roommanager_bookings')) {
      // MUDANÇAS: Todos os IDs unsigned e timestamps
      $query = "CREATE TABLE `glpi_plugin_roommanager_bookings` (
         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
         `plugin_roommanager_rooms_id` int(10) unsigned NOT NULL,
         `plugin_roommanager_slots_id` int(10) unsigned NOT NULL,
         `users_id` int(10) unsigned NOT NULL,
         `date` date NOT NULL,
         `name` varchar(255) DEFAULT NULL COMMENT 'Título do Evento',
         `group_id` varchar(64) DEFAULT NULL COMMENT 'ID para séries recorrentes',
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `uniq_booking` (`plugin_roommanager_rooms_id`, `plugin_roommanager_slots_id`, `date`),
         KEY `users_id` (`users_id`),
         KEY `group_id` (`group_id`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      
      $migration->addPostQuery($query);
   } else {
      // Bloco de atualização de versão anterior (Mantido por segurança)
      if (!$DB->fieldExists('glpi_plugin_roommanager_bookings', 'group_id')) {
         $migration->addField(
            'glpi_plugin_roommanager_bookings', 
            'group_id', 
            'varchar(64) DEFAULT NULL COMMENT \'ID para séries recorrentes\''
         );
         $migration->addKey('glpi_plugin_roommanager_bookings', 'group_id');
      }
   }

   $migration->executeMigration();

   return true;
}

function plugin_roommanager_uninstall() {
   global $DB;
   
   $tables = [
      'glpi_plugin_roommanager_bookings',
      'glpi_plugin_roommanager_slots',
      'glpi_plugin_roommanager_rooms'
   ];
   
   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->dropTable($table);
      }
   }
   
   return true;
}