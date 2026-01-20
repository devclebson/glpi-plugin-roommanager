<?php

namespace GlpiPlugin\Roommanager;

use CommonDBTM;
use Html;
use Session;

class Slot extends CommonDBTM {

   static $rightname = 'plugin_roommanager';

   static function getTypeName($nb = 0) {
      return "Horários (Slots)";
   }

   static function getIcon() {
      return "ti ti-clock";
   }

// --- PERMISSÕES BLINDADAS ---
   static function canView(): bool { return Session::haveRight('config', UPDATE); }
   static function canCreate(): bool { return Session::haveRight('config', UPDATE); }
   static function canUpdate(): bool { return Session::haveRight('config', UPDATE); }
   static function canDelete(): bool { return Session::haveRight('config', UPDATE); }

   function getRawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'start_time',
         'name'               => 'Início',
         'datatype'           => 'time'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'end_time',
         'name'               => 'Fim',
         'datatype'           => 'time'
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

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>Nome (Ex: 08:00 - 09:00)</td>";
      echo "<td>" . Html::input('name', ['value' => $this->fields['name']]) . "</td>";
      echo "<td>Ativo</td>";
      echo "<td>";
      Html::showCheckbox(['name' => 'is_active', 'checked' => $this->fields['is_active']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Início</td>";
      echo "<td>";
      echo Html::input('start_time', ['value' => $this->fields['start_time'], 'type' => 'time']);
      echo "</td>";
      
      echo "<td>Fim</td>";
      echo "<td>";
      echo Html::input('end_time', ['value' => $this->fields['end_time'], 'type' => 'time']);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }
}