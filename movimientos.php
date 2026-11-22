<?php

require 'funciones.php';
exigir_sesion();
require 'conexion.php';

$errores = [];
$form    = ['id_producto' => $_GET['producto'] ?? '', 'tipo' => 'entrada', 'cantidad' => '', 'observacion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['id_producto'] = $_POST['id_producto'] ?? '';
    $form['tipo']        = $_POST['tipo'] ?? '';
    $form['cantidad']    = trim($_POST['cantidad'] ?? '');
    $form['observacion'] = trim($_POST['observacion'] ?? '');


    $idProducto = filter_var($form['id_producto'], FILTER_VALIDATE_INT);
    $cantidad   = filter_var($form['cantidad'], FILTER_VALIDATE_INT);

    if ($idProducto === false) {
        $errores[] = 'Elige un producto.';
    }
    if (!in_array($form['tipo'], ['entrada', 'salida'], true)) {
        $errores[] = 'El tipo de movimiento no es válido.';
    }
    if ($cantidad === false || $cantidad <= 0) {
        $errores[] = 'La cantidad debe ser un número entero mayor a 0.';
    }

    if (!$errores) {
        try {
        
            $pdo->beginTransaction();

        
            $stmt = $pdo->prepare('SELECT stock_actual FROM producto WHERE id_producto = ? AND estado = 1 FOR UPDATE');
            $stmt->execute([$idProducto]);
            $stock = $stmt->fetchColumn();

            if ($stock === false) {
                throw new Exception('El producto no existe o está desactivado.');
            }
            $stock = (int)$stock;

        
            if ($form['tipo'] === 'salida' && $cantidad > $stock) {
                throw new Exception("Stock insuficiente: solo hay $stock unidades disponibles.");
            }

            $nuevo = $form['tipo'] === 'entrada' ? $stock + $cantidad : $stock - $cantidad;

        
            $pdo->prepare('INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario)
                           VALUES (?, ?, ?, ?, ?)')
                ->execute([
                    $form['tipo'], $cantidad,
                    $form['observacion'] !== '' ? $form['observacion'] : null,
                    $idProducto, $_SESSION['id_usuario']
                ]);

            $pdo->prepare('UPDATE producto SET stock_actual = ? WHERE id_producto = ?')
                ->execute([$nuevo, $idProducto]);

            $pdo->commit();

            avisar('ok', ($form['tipo'] === 'entrada' ? 'Entrada' : 'Salida')
                . " registrada. El nuevo stock es $nuevo.");
            header('Location: movimientos.php');  
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = ($ex instanceof PDOException)
                ? 'No se pudo registrar el movimiento. Inténtalo de nuevo.'
                : $ex->getMessage();
        }
    }
}

$productos = $pdo->query('SELECT id_producto, codigo, nombre, stock_actual
                          FROM producto WHERE estado = 1 ORDER BY nombre')
                 ->fetchAll(PDO::FETCH_ASSOC);

$historial = $pdo->query('SELECT m.fecha_hora, m.tipo, m.cantidad, m.observacion,
                                 p.nombre AS producto, u.nombre AS usuario
                          FROM movimiento m
                          JOIN producto p ON m.id_producto = p.id_producto
                          JOIN usuario u  ON m.id_usuario  = u.id_usuario
                          ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
                          LIMIT 15')
                 ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Movimientos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<?php $pagina = 'movimientos'; include 'barra.php'; ?>

<main class="angosto">
    <?php mostrar_aviso(); ?>

    <div class="encabezado">
        <div>
            <h1>Movimientos de stock</h1>
            <p class="resumen">Registra las entradas y salidas de productos.</p>
        </div>
    </div>

    <?php if ($errores): ?>
        <div class="aviso error" role="alert">
            <ul>
                <?php foreach ($errores as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="tarjeta" data-validar novalidate>
        <div class="campos">
            <div class="campo">
                <label for="id_producto">Producto</label>
                <select id="id_producto" name="id_producto" required>
                    <option value="">Selecciona un producto…</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= (int)$p['id_producto'] ?>"
                            <?= $p['id_producto'] == $form['id_producto'] ? 'selected' : '' ?>>
                            <?= e($p['codigo'] . ' · ' . $p['nombre']) ?> (stock: <?= (int)$p['stock_actual'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="ayuda"></span>
            </div>

            <div class="campo m6">
                <label for="tipo">Tipo de movimiento</label>
                <select id="tipo" name="tipo" required>
                    <option value="entrada" <?= $form['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada (suma stock)</option>
                    <option value="salida"  <?= $form['tipo'] === 'salida'  ? 'selected' : '' ?>>Salida (resta stock)</option>
                </select>
                <span class="ayuda"></span>
            </div>

            <div class="campo m6">
                <label for="cantidad">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" min="1" step="1" required
                       value="<?= e($form['cantidad']) ?>">
                <span class="ayuda"></span>
            </div>

            <div class="campo">
                <label for="observacion">Observación (opcional)</label>
                <input type="text" id="observacion" name="observacion" maxlength="255"
                       value="<?= e($form['observacion']) ?>">
                <span class="ayuda"></span>
            </div>
        </div>

        <div class="botones">
            <button type="submit" class="boton">Registrar movimiento</button>
        </div>
    </form>

    <h2 class="subtitulo">Últimos movimientos</h2>
    <div class="tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Usuario</th><th>Observación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $m): ?>
                <tr>
                    <td><?= e($m['fecha_hora']) ?></td>
                    <td><?= e($m['producto']) ?></td>
                    <td>
                        <span class="etiqueta <?= e($m['tipo']) ?>">
                            <?= $m['tipo'] === 'entrada' ? 'Entrada' : 'Salida' ?>
                        </span>
                    </td>
                    <td><?= (int)$m['cantidad'] ?></td>
                    <td><?= e($m['usuario']) ?></td>
                    <td><?= e($m['observacion'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$historial): ?>
                <tr><td colspan="6" class="vacio-fila">Aún no hay movimientos registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script src="validar.js"></script>
</body>
</html>
