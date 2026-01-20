<?php
include ("../../../inc/includes.php");
$room = new \GlpiPlugin\Roommanager\Room();
$room->display($_GET); // Cuida do formulário sozinho