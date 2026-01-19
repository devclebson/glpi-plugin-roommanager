<?php
/**
 * plugins/roommanager/hook.php
 */

function plugin_roommanager_install() {
   global $DB;

   // CORREÇÃO: Usamos \Migration (barra invertida) para acessar a classe Global do GLPI
   $migration = new \Migration(PLUGIN_ROOMMANAGER_VERSION);

   // 1. Tabela de Salas
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
      
      $migration->addPostQuery($query);
   }

   // 2. Tabela de Slots (Horários)
   if (!$DB->tableExists('glpi_plugin_roommanager_slots')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_slots` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `start_time` time NOT NULL,
         `end_time` time NOT NULL,
         `is_active` tinyint(1) NOT NULL DEFAULT '1',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      
      $migration->addPostQuery($query);
      
      // Inserts também vão via Migration para segurança
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
      $query = "CREATE TABLE `glpi_plugin_roommanager_bookings` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `plugin_roommanager_rooms_id` int(11) NOT NULL,
         `plugin_roommanager_slots_id` int(11) NOT NULL,
         `users_id` int(11) NOT NULL,
         `date` date NOT NULL,
         `name` varchar(255) DEFAULT NULL COMMENT 'Título do Evento',
         `date_creation` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `uniq_booking` (`plugin_roommanager_rooms_id`, `plugin_roommanager_slots_id`, `date`),
         KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
      
      $migration->addPostQuery($query);
   }

   // Executa
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
         $DB->query("DROP TABLE `$table`");
      }
   }
   return true;
}