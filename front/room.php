<?php
include ("../../../inc/includes.php");

use GlpiPlugin\Roommanager\Room;

Session::checkLoginUser();

// LÓGICA PADRÃO GLPI:
// 1. Se tem ID na URL (mesmo que seja -1 para criar), mostra o FORMULÁRIO.
if (isset($_GET["id"])) {
    $room = new Room();
    // O método display cuida de mostrar o form de Edição ou Criação (se ID for -1)
    $room->display($_GET);
} 
// 2. Se não tem ID, mostra a LISTA com o botão de Adicionar
else {
    Html::header(Room::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", "roommanager");
    
    // Mostra a tabela de pesquisa
    // O botão (+) Adicionar aparecerá automaticamente se o usuário tiver permissão de CREATE
    Search::show(Room::class);
    
    Html::footer();
}