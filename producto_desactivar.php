<?php
// Desactiva un producto (estado = 0). Nunca se elimina físicamente.
require 'funciones.php';
exigir_sesion();
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE producto SET estado = 0 WHERE id_producto = ? AND estado = 1');
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            avisar('ok', 'Producto desactivado. Sigue guardado en la base de datos, pero ya no aparece en el listado.');
        } else {
            avisar('error', 'El producto no existe o ya estaba desactivado.');
        }
    }
}

header('Location: productos.php');
exit;
