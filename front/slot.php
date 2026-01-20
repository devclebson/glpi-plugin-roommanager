<?php
include ("../../../inc/includes.php");

use GlpiPlugin\Roommanager\Slot;

Session::checkLoginUser();

if (isset($_GET["id"])) {
    $slot = new Slot();
    $slot->display($_GET);
} 
else {
    Html::header(Slot::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", "roommanager");
    Search::show(Slot::class);
    Html::footer();
}