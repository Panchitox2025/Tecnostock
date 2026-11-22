<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
require 'conexion.php';

$errores = [];
$ok      = '';
$vacio   = ['id_producto' => '', 'tipo' => 'entrada', 'cantidad' => '', 'observacion' => ''];
$form    = $vacio;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['id_producto']  = $_POST['id_producto'] ?? '';
    $form['tipo']         = $_POST['tipo'] ?? '';
    $form['cantidad']     = $_POST['cantidad'] ?? '';
    $form['observacion']  = trim($_POST['observacion'] ?? '');

    // Validaciones en el servidor
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

            // Bloquea la fila del producto mientras se procesa el movimiento
            $stmt = $pdo->prepare('SELECT stock_actual FROM producto WHERE id_producto = ? AND estado = 1 FOR UPDATE');
            $stmt->execute([$idProducto]);
            $stock = $stmt->fetchColumn();

            if ($stock === false) {
                throw new Exception('El producto no existe o está desactivado.');
            }
            $stock = (int)$stock;

            if ($form['tipo'] === 'salida' && $cantidad > $stock) {
                throw new Exception("Stock insuficiente: hay $stock unidades disponibles.");
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
            $ok   = "Movimiento registrado. Nuevo stock: $nuevo.";
            $form = $vacio;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = ($e instanceof PDOException)
                ? 'No se pudo registrar el movimiento.'
                : $e->getMessage();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 860px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Movimientos de stock</h1>
        <a href="productos.php" class="btn btn-outline-secondary btn-sm">Volver a productos</a>
    </div>

    <?php if ($ok): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($ok) ?></div>
    <?php endif; ?>
    <?php foreach ($errores as $e): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="post" id="formMov" class="card card-body mb-4" novalidate>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="id_producto">Producto</label>
                <select class="form-select" id="id_producto" name="id_producto" required>
                    <option value="">Selecciona…</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= (int)$p['id_producto'] ?>"
                            <?= $p['id_producto'] == $form['id_producto'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['codigo'] . ' · ' . $p['nombre']) ?> (stock: <?= (int)$p['stock_actual'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="tipo">Tipo</label>
                <select class="form-select" id="tipo" name="tipo" required>
                    <option value="entrada" <?= $form['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="salida"  <?= $form['tipo'] === 'salida'  ? 'selected' : '' ?>>Salida</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cantidad">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" step="1" required
                       value="<?= htmlspecialchars($form['cantidad']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="observacion">Observación (opcional)</label>
                <input type="text" class="form-control" id="observacion" name="observacion" maxlength="255"
                       value="<?= htmlspecialchars($form['observacion']) ?>">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Registrar movimiento</button>
        </div>
    </form>

    <h2 class="h5">Últimos movimientos</h2>
    <table class="table table-sm table-striped bg-white">
        <thead>
            <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Usuario</th><th>Observación</th></tr>
        </thead>
        <tbody>
        <?php foreach ($historial as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['fecha_hora']) ?></td>
                <td><?= htmlspecialchars($m['producto']) ?></td>
                <td><?= $m['tipo'] === 'entrada' ? 'Entrada' : 'Salida' ?></td>
                <td><?= (int)$m['cantidad'] ?></td>
                <td><?= htmlspecialchars($m['usuario']) ?></td>
                <td><?= htmlspecialchars($m['observacion'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$historial): ?>
            <tr><td colspan="6" class="text-muted">Aún no hay movimientos.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Validación en el navegador (se repite en PHP)
    const form = document.getElementById('formMov');
    form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) {
            e.preventDefault();
        }
        form.classList.add('was-validated');
    });
</script>
</body>
</html>