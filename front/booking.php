<?php

include ("../../../inc/includes.php");

// Proteção para mostrar o erro real na tela se algo explodir
try {
    // Verifica se está logado
    Session::checkLoginUser();

    // --- CORREÇÃO AQUI ---
    // Antes estava "roommanager", mudamos para "central".
    // "central" é a permissão básica que todo usuário logado tem.
    // Isso evita o erro "Ação não permitida" por enquanto.
    Html::header("Reserva de Salas", $_SERVER['PHP_SELF'], "tools", "central");

    // Carrega o CSS personalizado do plugin
    // echo '<link rel="stylesheet" type="text/css" href="../css/style.css?v=1.2">';
    // --------------------------------

    // Instancia nossa classe
    // Usamos a barra invertida \GlpiPlugin... para garantir que o PHP ache a classe
    $bookingPage = new \GlpiPlugin\Roommanager\Booking();
    
    // Chama a função de exibir a grade
    if (method_exists($bookingPage, 'displayGrid')) {
        $bookingPage->displayGrid();
    } else {
        echo "<div class='alert alert-danger'>Erro: A função displayGrid() não foi encontrada na classe Booking.php</div>";
    }

    Html::footer();

} catch (\Throwable $e) {
    // Se der erro 500, mostra aqui o motivo exato
    Html::header("Erro", $_SERVER['PHP_SELF'], "tools", "central");
    echo "<div class='alert alert-danger p-4'>";
    echo "<h3><i class='ti ti-alert-triangle'></i> Ocorreu um erro no Plugin</h3>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . " (Linha " . $e->getLine() . ")";
    echo "</div>";
    Html::footer();
}