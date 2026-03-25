<?php

namespace GlpiPlugin\Roommanager;

use CommonDBTM;
use Html;
use Session;

class Room extends CommonDBTM {
   static $rightname = 'config';

   static function getTypeName($nb = 0) { return "Salas"; }
   static function getIcon() { return "ti ti-door"; }

   static function canView(): bool { return Session::haveRight('config', UPDATE); }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>Nome</td><td>";
      echo Html::input('name', ['value' => $this->fields['name']]);
      echo "</td><td>Capacidade</td><td>";
      echo Html::input('capacity', ['value' => $this->fields['capacity'], 'type' => 'number']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>Localização</td><td>";
      \Location::dropdown(['value' => $this->fields['locations_id'], 'name' => 'locations_id']);
      echo "</td><td>Ativo</td><td>";
      Html::showCheckbox(['name' => 'is_active', 'checked' => $this->fields['is_active']]);
      echo "</td></tr>";

      $this->showFormButtons($options);
      return true;
   }
}