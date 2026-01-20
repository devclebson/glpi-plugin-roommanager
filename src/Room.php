<?php

namespace GlpiPlugin\Roommanager;

use CommonDBTM;
use Html;
use Session;

class Room extends CommonDBTM {

   static $rightname = 'plugin_roommanager';

   static function getTypeName($nb = 0) {
      return "Salas";
   }

   static function getIcon() {
      return "ti ti-door";
   }

   // --- PERMISSÕES ---
   // Usamos 'config' e UPDATE. Isso garante que só quem tem perfil de ADMIN/SUPER-ADMIN acessa.
   // O Self-Service vai receber "false" aqui e o menu nem vai aparecer para ele.
   
   static function canView(): bool {
      return Session::haveRight('config', UPDATE);
   }

   static function canCreate(): bool {
      return Session::haveRight('config', UPDATE);
   }

   static function canUpdate(): bool {
      return Session::haveRight('config', UPDATE);
   }

   static function canDelete(): bool {
      return Session::haveRight('config', UPDATE); // ou PURGE
   }
   // ------------------

   // Define as colunas que aparecem na LISTA
   function getRawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'capacity',
         'name'               => 'Capacidade',
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => 'glpi_locations',
         'field'              => 'completename',
         'name'               => 'Localização',
         'datatype'           => 'dropdown'
      ];
      
      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }

   static function getMenuContent() {
      // O menu só aparece se o usuário tiver permissão de View
      if (!static::canView()) {
         return false;
      }
      $menu = [];
      $menu['title'] = self::getTypeName(1);
      $menu['page']  = '/plugins/roommanager/front/room.php';
      $menu['icon']  = self::getIcon();
      return $menu;
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>Nome da Sala</td>";
      echo "<td>";
      echo Html::input('name', ['value' => $this->fields['name']]);
      echo "</td>";
      
      echo "<td>Capacidade</td>";
      echo "<td>";
      echo Html::input('capacity', ['value' => $this->fields['capacity'], 'type' => 'number']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Localização (Sede)</td>";
      echo "<td>";
      \Location::dropdown(['value' => $this->fields['locations_id']]);
      echo "</td>";
      
      echo "<td>Ativo</td>";
      echo "<td>";
      Html::showCheckbox(['name' => 'is_active', 'checked' => $this->fields['is_active']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Comentários</td>";
      echo "<td colspan='3'>";
      echo "<textarea class='form-control' name='comment'>" . $this->fields['comment'] . "</textarea>";
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }
}