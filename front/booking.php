<?php

include ("../../../inc/includes.php");

// Proteção para mostrar o erro real na tela se algo explodir
try {
    // Verifica se está logado
    Session::checkLoginUser();

    // --- LÓGICA DE CABEÇALHO INTELIGENTE ---
    if (Session::getCurrentInterface() == "helpdesk") {
        // Cabeçalho para Interface Simplificada (Self-Service)
        Html::helpHeader("Reserva de Salas", "plugins", "roommanager");
    } else {
        // Cabeçalho para Interface Padrão (Admin/Técnico)
        Html::header("Reserva de Salas", $_SERVER['PHP_SELF'], "tools", "central");
    }
    // ---------------------------------------

    // Instancia nossa classe
    $bookingPage = new \GlpiPlugin\Roommanager\Booking();
    
    // Chama a função de exibir a grade
    if (method_exists($bookingPage, 'displayGrid')) {
        $bookingPage->displayGrid();
    } else {
        echo "<div class='alert alert-danger'>Erro: A função displayGrid() não foi encontrada na classe Booking.php</div>";
    }

    // --- LÓGICA DE RODAPÉ INTELIGENTE ---
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::helpFooter();
    } else {
        Html::footer();
    }

} catch (\Throwable $e) {
    Html::header("Erro", $_SERVER['PHP_SELF'], "tools", "central");
    echo "<div class='alert alert-danger p-4'>";
    echo "<h3><i class='ti ti-alert-triangle'></i> Ocorreu um erro no Plugin</h3>";
    echo "<strong>Mensagem:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . " (Linha " . $e->getLine() . ")";
    echo "</div>";
    Html::footer();
}