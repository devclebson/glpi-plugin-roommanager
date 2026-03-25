<?php

namespace GlpiPlugin\Roommanager;

use CommonDBTM;
use Html;
use Session;
use Dropdown;

class Config extends CommonDBTM {

   static function getTypeName($nb = 0) {
      return "Configuração do Room Manager";
   }

public function showManualForm() {
      $this->getFromDB(1);

      echo "<form method='post' action='config.php' style='background: #fff; padding: 20px; border: 1px solid #ddd;'>";
      
      // Token CSRF - Nome padrão do GLPI
      echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><td>Início da Grade:</td><td>";
      Dropdown::showNumber('day_start_hour', ['value' => $this->fields['day_start_hour'], 'min' => 0, 'max' => 23]);
      echo "h</td></tr>";

      echo "<tr><td>Término da Grade:</td><td>";
      Dropdown::showNumber('day_end_hour', ['value' => $this->fields['day_end_hour'], 'min' => 0, 'max' => 23]);
      echo "h</td></tr>";

      echo "<tr><td>Intervalo de Slots:</td><td>";
      Dropdown::showFromArray('slot_interval', [5 => '5 min', 15 => '15 min', 30 => '30 min', 60 => '1 hora'], ['value' => $this->fields['slot_interval']]);
      echo "</td></tr>";

      echo "<tr><td>Auto-Refresh:</td><td>";
      Dropdown::showNumber('refresh_interval', ['value' => $this->fields['refresh_interval'], 'min' => 30, 'max' => 900, 'step' => 30]);
      echo "s</td></tr>";
      echo "</table>";
      
      echo "<div class='center' style='margin-top: 20px;'>";
      echo "<input type='submit' name='update_manual' value='Salvar Configurações' class='btn btn-primary'>";
      echo "</div>";
      
      echo "</form>";
   }
}