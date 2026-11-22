<?php

require 'funciones.php';
exigir_sesion();
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
        avisar('error', 'El producto que intentas editar no existe.');
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
    $p['precio']       = trim($_POST['precio'] ?? '');
    $p['stock_actual'] = trim($_POST['stock_actual'] ?? '');
    $p['stock_minimo'] = trim($_POST['stock_minimo'] ?? '');
    $p['id_categoria'] = $_POST['id_categoria'] ?? '';


    if ($p['codigo'] === '') {
        $errores[] = 'El código es obligatorio.';
    }
    if ($p['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if (!is_numeric($p['precio']) || $p['precio'] < 0) {
        $errores[] = 'El precio debe ser un número igual o mayor a 0.';
    }
    if (filter_var($p['stock_actual'], FILTER_VALIDATE_INT) === false || $p['stock_actual'] < 0) {
        $errores[] = 'El stock actual debe ser un número entero igual o mayor a 0.';
    }
    if (filter_var($p['stock_minimo'], FILTER_VALIDATE_INT) === false || $p['stock_minimo'] < 0) {
        $errores[] = 'El stock mínimo debe ser un número entero igual o mayor a 0.';
    }
    if (filter_var($p['id_categoria'], FILTER_VALIDATE_INT) === false) {
        $errores[] = 'Elige una categoría.';
    }


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
                $sql = 'UPDATE producto
                        SET nombre = ?, descripcion = ?, precio = ?,
                            stock_actual = ?, stock_minimo = ?, id_categoria = ?
                        WHERE id_producto = ?';
                $pdo->prepare($sql)->execute([
                    $p['nombre'], $p['descripcion'], $p['precio'],
                    $p['stock_actual'], $p['stock_minimo'], $p['id_categoria'], $id
                ]);
                avisar('ok', 'Producto actualizado correctamente.');
            } else {
                $sql = 'INSERT INTO producto (codigo, nombre, descripcion, precio,
                                              stock_actual, stock_minimo, id_categoria)
                        VALUES (?, ?, ?, ?, ?, ?, ?)';
                $pdo->prepare($sql)->execute([
                    $p['codigo'], $p['nombre'], $p['descripcion'], $p['precio'],
                    $p['stock_actual'], $p['stock_minimo'], $p['id_categoria']
                ]);
                avisar('ok', 'Producto creado correctamente.');
            }
            header('Location: productos.php');
            exit;
        } catch (PDOException $ex) {
            $errores[] = (($ex->errorInfo[1] ?? 0) == 1062)
                ? 'Ya existe un producto con ese código.'
                : 'No se pudo guardar el producto. Revisa los datos e inténtalo de nuevo.';
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<?php $pagina = 'productos'; include 'barra.php'; ?>

<main class="angosto">
    <div class="encabezado">
        <h1><?= $editando ? 'Editar producto' : 'Nuevo producto' ?></h1>
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
            <div class="campo m4">
                <label for="codigo">Código</label>
                <input type="text" id="codigo" name="codigo" maxlength="30" required
                       value="<?= e($p['codigo']) ?>" <?= $editando ? 'readonly' : '' ?>>
                <?php if ($editando): ?>
                    <span class="nota">El código no se puede cambiar.</span>
                <?php endif; ?>
                <span class="ayuda"></span>
            </div>

            <div class="campo m8">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       value="<?= e($p['nombre']) ?>">
                <span class="ayuda"></span>
            </div>

            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="2" maxlength="255"><?= e($p['descripcion']) ?></textarea>
                <span class="ayuda"></span>
            </div>

            <div class="campo m6">
                <label for="id_categoria">Categoría</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">Selecciona una categoría…</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int)$c['id_categoria'] ?>"
                            <?= $c['id_categoria'] == $p['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="ayuda"></span>
            </div>

            <div class="campo m6">
                <label for="precio">Precio ($)</label>
                <input type="number" id="precio" name="precio" min="0" step="0.01" required
                       value="<?= e($p['precio']) ?>">
                <span class="ayuda"></span>
            </div>

            <div class="campo m6">
                <label for="stock_actual">Stock actual</label>
                <input type="number" id="stock_actual" name="stock_actual" min="0" step="1" required
                       value="<?= e($p['stock_actual']) ?>">
                <span class="ayuda"></span>
            </div>

            <div class="campo m6">
                <label for="stock_minimo">Stock mínimo</label>
                <input type="number" id="stock_minimo" name="stock_minimo" min="0" step="1" required
                       value="<?= e($p['stock_minimo']) ?>">
                <span class="nota">Si el stock baja de este número, se muestra la alerta.</span>
                <span class="ayuda"></span>
            </div>
        </div>

        <div class="botones">
            <button type="submit" class="boton">Guardar</button>
            <a href="productos.php" class="boton secundario">Cancelar</a>
        </div>
    </form>
</main>

<script src="validar.js"></script>
</body>
</html>
