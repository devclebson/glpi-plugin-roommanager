<?php

/**
 * Função executada ao clicar em "Instalar"
 */
function plugin_roommanager_install() {
   global $DB;

   // TODO: Criar tabelas do banco de dados (Salas, Reservas, Slots)
   
   return true;
}

/**
 * Função executada ao clicar em "Desinstalar"
 */
function plugin_roommanager_uninstall() {
   global $DB;

   // TODO: Remover tabelas
   
   return true;
}