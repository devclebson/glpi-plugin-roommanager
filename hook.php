<?php
/**
 * plugins/roommanager/hook.php
 */

use Glpi\Plugin\Hooks;

function plugin_roommanager_install() {
   global $DB;

   $migration = new \Migration(PLUGIN_ROOMMANAGER_VERSION);

   // 1. Tabela de Salas (Mantém igual)
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

   // 2. Tabela de Slots (Mantém apenas para desenhar a régua visual)
   if (!$DB->tableExists('glpi_plugin_roommanager_slots')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_slots` (
         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `start_time` time NOT NULL,
         `end_time` time NOT NULL,
         `is_active` tinyint(1) NOT NULL DEFAULT '1',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      $migration->addPostQuery($query);
      
      // Insere slots padrão
      $migration->addPostQuery("INSERT INTO `glpi_plugin_roommanager_slots` (name, start_time, end_time) VALUES 
                  ('08:00', '08:00:00', '09:00:00'),
                  ('09:00', '09:00:00', '10:00:00'),
                  ('10:00', '10:00:00', '11:00:00'),
                  ('11:00', '11:00:00', '12:00:00'),
                  ('13:00', '13:00:00', '14:00:00'),
                  ('14:00', '14:00:00', '15:00:00'),
                  ('15:00', '15:00:00', '16:00:00'),
                  ('16:00', '16:00:00', '17:00:00'),
                  ('17:00', '17:00:00', '18:00:00'),
                  ('18:00', '18:00:00', '19:00:00')");
   }

   // 3. Tabela de Reservas (AQUI É A MUDANÇA GRANDE)
   if (!$DB->tableExists('glpi_plugin_roommanager_bookings')) {
      $query = "CREATE TABLE `glpi_plugin_roommanager_bookings` (
         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
         `plugin_roommanager_rooms_id` int(10) unsigned NOT NULL,
         `plugin_roommanager_slots_id` int(10) unsigned DEFAULT NULL, 
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
   } else {
      // --- MIGRAÇÃO PARA QUEM JÁ TEM O PLUGIN INSTALADO ---
      
      // 1. Adiciona start_time se não existir
      if (!$DB->fieldExists('glpi_plugin_roommanager_bookings', 'start_time')) {
         $migration->addField('glpi_plugin_roommanager_bookings', 'start_time', 'time NOT NULL AFTER date');
         $migration->addKey('glpi_plugin_roommanager_bookings', 'start_time');
      }
      
      // 2. Adiciona end_time se não existir
      if (!$DB->fieldExists('glpi_plugin_roommanager_bookings', 'end_time')) {
         $migration->addField('glpi_plugin_roommanager_bookings', 'end_time', 'time NOT NULL AFTER start_time');
         $migration->addKey('glpi_plugin_roommanager_bookings', 'end_time');
      }

      // 3. Relaxa o Slot ID (agora pode ser nulo, pois vamos usar horário livre)
      $migration->changeField('glpi_plugin_roommanager_bookings', 'plugin_roommanager_slots_id', 'plugin_roommanager_slots_id', 'int(10) unsigned DEFAULT NULL');
      
      // 4. MIGRAR DADOS ANTIGOS: Pega o horário do Slot e salva na Reserva
      $migration->addPostQuery("
         UPDATE glpi_plugin_roommanager_bookings b
         INNER JOIN glpi_plugin_roommanager_slots s ON b.plugin_roommanager_slots_id = s.id
         SET b.start_time = s.start_time, b.end_time = s.end_time
         WHERE b.start_time IS NULL OR b.start_time = '00:00:00'
      ");
   }

   $migration->executeMigration();
   return true;
}

function plugin_roommanager_uninstall() {
   global $DB;
   $tables = ['glpi_plugin_roommanager_bookings', 'glpi_plugin_roommanager_slots', 'glpi_plugin_roommanager_rooms'];
   foreach ($tables as $table) {
      if ($DB->tableExists($table)) $DB->dropTable($table);
   }
   return true;
}