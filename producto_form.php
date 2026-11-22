<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
require 'conexion.php';

$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editando = $id > 0;
$errores  = [];

$p = ['codigo' => '', 'nombre' => '', 'descripcion' => '', 'precio' => '',
      'stock_actual' => 0, 'stock_minimo' => 0, 'id_categoria' => ''];

if ($editando) {
    $stmt = $pdo->prepare('SELECT * FROM producto WHERE id_producto = ?');
    $stmt->execute([$id]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$fila) {
        header('Location: productos.php');
        exit;
    }
    $p = $fila;
}

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre')
                  ->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$editando) {
        $p['codigo'] = trim($_POST['codigo'] ?? '');
    }
    $p['nombre']       = trim($_POST['nombre'] ?? '');
    $p['descripcion']  = trim($_POST['descripcion'] ?? '');
    $p['precio']       = $_POST['precio'] ?? '';
    $p['stock_actual'] = $_POST['stock_actual'] ?? '';
    $p['stock_minimo'] = $_POST['stock_minimo'] ?? '';
    $p['id_categoria'] = $_POST['id_categoria'] ?? '';

    // Validaciones en el servidor (reglas 1 y 2)
    if ($p['codigo'] === '') $errores[] = 'El código es obligatorio.';
    if ($p['nombre'] === '') $errores[] = 'El nombre es obligatorio.';
    if (!is_numeric($p['precio']) || $p['precio'] < 0) {
        $errores[] = 'El precio debe ser un número igual o mayor a 0.';
    }
    if (filter_var($p['stock_actual'], FILTER_VALIDATE_INT) === false || $p['stock_actual'] < 0) {
        $errores[] = 'El stock actual debe ser un entero igual o mayor a 0.';
    }
    if (filter_var($p['stock_minimo'], FILTER_VALIDATE_INT) === false || $p['stock_minimo'] < 0) {
        $errores[] = 'El stock mínimo debe ser un entero igual o mayor a 0.';
    }
    if (filter_var($p['id_categoria'], FILTER_VALIDATE_INT) === false) {
        $errores[] = 'Elige una categoría.';
    }

    // Código no repetido (regla 1)
    if (!$errores && !$editando) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE codigo = ?');
        $stmt->execute([$p['codigo']]);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = 'Ya existe un producto con ese código.';
        }
    }

    if (!$errores) {
        try {
            if ($editando) {
                $sql = 'UPDATE producto SET nombre = ?, descripcion = ?, precio = ?,
                        stock_actual = ?, stock_minimo = ?, id_categoria = ?
                        WHERE id_producto = ?';
                $pdo->prepare($sql)->execute([
                    $p['nombre'], $p['descripcion'], $p['precio'],
                    $p['stock_actual'], $p['stock_minimo'], $p['id_categoria'], $id
                ]);
            } else {
                $sql = 'INSERT INTO producto (codigo, nombre, descripcion, precio,
                        stock_actual, stock_minimo, id_categoria)
                        VALUES (?, ?, ?, ?, ?, ?, ?)';
                $pdo->prepare($sql)->execute([
                    $p['codigo'], $p['nombre'], $p['descripcion'], $p['precio'],
                    $p['stock_actual'], $p['stock_minimo'], $p['id_categoria']
                ]);
            }
            header('Location: productos.php');
            exit;
        } catch (PDOException $e) {
            $errores[] = 'No se pudo guardar el producto. Revisa los datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - <?= $editando ? 'Editar' : 'Nuevo' ?> producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width: 640px;">
    <h1 class="h3 mb-4"><?= $editando ? 'Editar producto' : 'Nuevo producto' ?></h1>

    <?php foreach ($errores as $e): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="post" id="formProducto" novalidate>
        <div class="mb-3">
            <label class="form-label" for="codigo">Código</label>
            <input type="text" class="form-control" id="codigo" name="codigo" maxlength="30" required
                   value="<?= htmlspecialchars($p['codigo']) ?>" <?= $editando ? 'readonly' : '' ?>>
        </div>
        <div class="mb-3">
            <label class="form-label" for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" required
                   value="<?= htmlspecialchars($p['nombre']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="255"><?= htmlspecialchars($p['descripcion']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label" for="id_categoria">Categoría</label>
            <select class="form-select" id="id_categoria" name="id_categoria" required>
                <option value="">Selecciona…</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id_categoria'] ?>" <?= $c['id_categoria'] == $p['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="precio">Precio</label>
                <input type="number" class="form-control" id="precio" name="precio" min="0" step="0.01" required
                       value="<?= htmlspecialchars($p['precio']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="stock_actual">Stock actual</label>
                <input type="number" class="form-control" id="stock_actual" name="stock_actual" min="0" step="1" required
                       value="<?= htmlspecialchars($p['stock_actual']) ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="stock_minimo">Stock mínimo</label>
                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="0" step="1" required
                       value="<?= htmlspecialchars($p['stock_minimo']) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="productos.php" class="btn btn-outline-secondary">Cancelar</a>
    </form>
</div>

<script>
    // Validación en el navegador (se repite en PHP)
    const form = document.getElementById('formProducto');
    form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) {
            e.preventDefault();
        }
        form.classList.add('was-validated');
    });
</script>
</body>
</html>