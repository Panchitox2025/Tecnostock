<?php
session_start();
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Bienvenido, <?= htmlspecialchars($_SESSION["nombre"]) ?></h2>
    <p>Sistema de inventario TecnoStock</p>
    <a href="productos.php" class="btn btn-primary">Ver productos</a>
    <a href="logout.php" class="btn btn-outline-secondary">Cerrar sesión</a>
</div>
</body>
</html>