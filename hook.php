<?php
/**
 * plugins/roommanager/hook.php
 */

function plugin_roommanager_install() {
   global $DB;

   $migration = new \Migration(PLUGIN_ROOMMANAGER_VERSION);

   if (!$DB->tableExists('glpi_plugin_roommanager_rooms')) {
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
         KEY `is_deleted` (`is_deleted`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      $migration->addPostQuery($query);
   }

   if (!$DB->tableExists('glpi_plugin_roommanager_configs')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_configs` (
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `day_start_hour` int(11) DEFAULT '8',
         `day_end_hour` int(11) DEFAULT '19',
         `slot_interval` int(11) DEFAULT '15',
         `refresh_interval` int(11) DEFAULT '300',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      $migration->addPostQuery($query);
      
      $migration->addPostQuery("INSERT INTO `glpi_plugin_roommanager_configs` 
                                (id, day_start_hour, day_end_hour, slot_interval, refresh_interval) 
                                VALUES (1, 8, 19, 15, 300) 
                                ON DUPLICATE KEY UPDATE id=id;");
   }

   if (!$DB->tableExists('glpi_plugin_roommanager_bookings')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_bookings` (
         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
         `plugin_roommanager_rooms_id` int(10) unsigned NOT NULL,
         `users_id` int(10) unsigned NOT NULL,
         `date` date NOT NULL,
         `start_time` time NOT NULL, 
         `end_time` time NOT NULL,
         `name` varchar(255) DEFAULT NULL,
         `group_id` varchar(64) DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `users_id` (`users_id`),
         KEY `date_idx` (`date`),
         KEY `time_idx` (`start_time`, `end_time`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      $migration->addPostQuery($query);
   }

   $migration->executeMigration();
   return true;
}

function plugin_roommanager_uninstall() {
   global $DB;
   $tables = [
      'glpi_plugin_roommanager_bookings', 
      'glpi_plugin_roommanager_rooms',
      'glpi_plugin_roommanager_configs'
   ];
   foreach ($tables as $table) {
      if ($DB->tableExists($table)) {
         $DB->dropTable($table);
      }
   }
   return true;
}