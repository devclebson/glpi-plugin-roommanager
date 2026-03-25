<?php
include ("../../../inc/includes.php");
use GlpiPlugin\Roommanager\Config;

Session::checkRight("config", UPDATE);
$config = new Config();
$message = "";

if (isset($_POST["update_manual"])) {
    // Validação manual do token para evitar o erro fatal do CheckCSRF
    if (!isset($_POST['_glpi_csrf_token']) || !Session::validateCSRFToken($_POST['_glpi_csrf_token'])) {
        Html::displayErrorAndDie("Token de segurança inválido ou expirado. Recarregue a página.");
    }

    global $DB;
    $result = $DB->update(
        'glpi_plugin_roommanager_configs', [
            'day_start_hour'   => $_POST['day_start_hour'],
            'day_end_hour'     => $_POST['day_end_hour'],
            'slot_interval'    => $_POST['slot_interval'],
            'refresh_interval' => $_POST['refresh_interval']
        ], [
            'id' => 1
        ]
    );

    if ($result) {
        $message = "<div class='alert alert-success'>Configurações salvas com sucesso!</div>";
    }
}

Html::header("Configuração", $_SERVER['PHP_SELF'], "config", "plugins", "roommanager");
echo "<div style='margin: 20px;'>";
echo "<h2>Configuração Independente</h2>";
echo $message;
$config->showManualForm();
echo "</div>";
Html::footer();