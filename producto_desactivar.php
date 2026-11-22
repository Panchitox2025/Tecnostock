<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE producto SET estado = 0 WHERE id_producto = ?');
        $stmt->execute([$id]);
    }
}
header('Location: productos.php');
exit;