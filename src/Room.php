<?php

namespace GlpiPlugin\Roommanager;

use CommonDBTM;
use Html;
use Session;

class Room extends CommonDBTM {

   // Vincula a classe à tabela correta criada no hook.php
   static $rightname = 'plugin_roommanager';

   static function getTypeName($nb = 0) {
      return "Salas";
   }

   static function getIcon() {
      return "ti ti-door";
   }

   /**
    * Define onde o menu vai aparecer
    */
   static function getMenuContent() {
      $menu = [];
      $menu['title'] = self::getTypeName(1);
      $menu['page']  = '/plugins/roommanager/front/room.php'; // Vamos precisar criar esse front rapidinho depois ou usar o padrão form
      $menu['icon']  = self::getIcon();
      return $menu;
   }

   /**
    * Cria as abas padrões (Principal, Logs, etc)
    */
   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      return $ong;
   }

   /**
    * Desenha o Formulário de Cadastro
    */
   function showForm($ID, $options = []) {
      global $DB;
      
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
      // Dropdown nativo do GLPI para Locations
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