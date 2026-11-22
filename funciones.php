<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function exigir_sesion(): void
{
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: login.php');
        exit;
    }
}


function e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}


function avisar(string $tipo, string $mensaje): void
{
    $_SESSION['aviso'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}


function mostrar_aviso(): void
{
    if (!empty($_SESSION['aviso'])) {
        $a = $_SESSION['aviso'];
        unset($_SESSION['aviso']);
        echo '<div class="aviso ' . e($a['tipo']) . '" role="status">' . e($a['mensaje']) . '</div>';
    }
}
