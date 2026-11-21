<?php
require "conexion.php";

$nombre = "Jefe";
$correo = "admin@tecnostock.cl";
$clave  = password_hash("1234", PASSWORD_DEFAULT);

$sql = "INSERT INTO usuario (nombre, correo, clave) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nombre, $correo, $clave]);

echo "Usuario creado";