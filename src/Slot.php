<?php

namespace GlpiPlugin\Roommanager;

use CommonDBTM;
use Html;

class Slot extends CommonDBTM {

   static $rightname = 'plugin_roommanager';

   static function getTypeName($nb = 0) {
      return "Horários (Slots)";
   }

   static function getIcon() {
      return "ti ti-clock";
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
      echo "<td>Nome (Ex: 1º Aula)</td>";
      echo "<td>" . Html::input('name', ['value' => $this->fields['name']]) . "</td>";
      echo "<td>Ativo</td>";
      echo "<td>";
      Html::showCheckbox(['name' => 'is_active', 'checked' => $this->fields['is_active']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>Início</td>";
      echo "<td>";
      // Campo de hora simples
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