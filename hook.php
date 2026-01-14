<?php
/**
 * hook.php
 */

function plugin_roommanager_install() {
   global $DB;

   // 1. Tabela de Salas (Rooms)
   if (!$DB->tableExists('glpi_plugin_roommanager_rooms')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_rooms` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `locations_id` int(11) NOT NULL DEFAULT '0',
         `capacity` int(11) NOT NULL DEFAULT '0',
         `is_active` tinyint(1) NOT NULL DEFAULT '1',
         `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
         `comment` text,
         `date_mod` datetime DEFAULT NULL,
         `date_creation` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `locations_id` (`locations_id`),
         KEY `is_deleted` (`is_deleted`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      $DB->query($query) or die($DB->error());
   }

   // 2. Tabela de Slots de Tempo (Time Slots)
   if (!$DB->tableExists('glpi_plugin_roommanager_slots')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_slots` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL COMMENT 'Ex: Aula 1',
         `start_time` time NOT NULL,
         `end_time` time NOT NULL,
         `is_active` tinyint(1) NOT NULL DEFAULT '1',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      $DB->query($query) or die($DB->error());
      
      // Seed inicial opcional
      $DB->query("INSERT INTO `glpi_plugin_roommanager_slots` (name, start_time, end_time) VALUES ('Manhã 1', '08:00:00', '09:00:00'), ('Manhã 2', '09:00:00', '10:00:00')");
   }

   // 3. Tabela de Reservas (Bookings)
   if (!$DB->tableExists('glpi_plugin_roommanager_bookings')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_bookings` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `plugin_roommanager_rooms_id` int(11) NOT NULL,
         `plugin_roommanager_slots_id` int(11) NOT NULL,
         `users_id` int(11) NOT NULL,
         `date` date NOT NULL,
         `date_creation` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `uniq_booking` (`plugin_roommanager_rooms_id`, `plugin_roommanager_slots_id`, `date`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      $DB->query($query) or die($DB->error());
   }

   return true;
}

function plugin_roommanager_uninstall() {
   global $DB;
   // Opcional: Dropar tabelas ao desinstalar
   $tables = [
      'glpi_plugin_roommanager_bookings',
      'glpi_plugin_roommanager_slots',
      'glpi_plugin_roommanager_rooms'
   ];
   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->query("DROP TABLE `$table`");
      }
   }
   return true;
}