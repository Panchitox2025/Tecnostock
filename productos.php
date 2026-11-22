<?php
require 'funciones.php';
exigir_sesion();
require 'conexion.php';

$buscar = trim($_GET['buscar'] ?? '');
$like   = "%$buscar%";

$sql = "SELECT p.*, c.nombre AS categoria
        FROM producto p
        JOIN categoria c ON p.id_categoria = c.id_categoria
        WHERE p.estado = 1
          AND (p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)
        ORDER BY p.nombre";
$stmt = $pdo->prepare($sql);
$stmt->execute([$like, $like, $like]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bajos = 0;
foreach ($productos as $p) {
    if ($p['stock_actual'] < $p['stock_minimo']) {
        $bajos++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Productos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<?php $pagina = 'productos'; include 'barra.php'; ?>

<main>
    <?php mostrar_aviso(); ?>

    <div class="encabezado">
        <div>
            <h1>Productos</h1>
            <p class="resumen">
                <?= count($productos) ?> <?= count($productos) === 1 ? 'producto' : 'productos' ?>
                <?php if ($bajos > 0): ?>
                    · <?= $bajos ?> con stock bajo
                <?php endif; ?>
            </p>
        </div>
        <a class="boton" href="producto_form.php">+ Nuevo producto</a>
    </div>

    <form class="buscador" method="get">
        <input type="text" name="buscar" placeholder="Buscar por código, nombre o categoría"
               aria-label="Buscar productos" value="<?= e($buscar) ?>">
        <button type="submit">Buscar</button>
        <?php if ($buscar !== ''): ?>
            <a href="productos.php">Quitar filtro</a>
        <?php endif; ?>
    </form>

    <?php if (!$productos): ?>
        <p class="vacio">No hay productos que coincidan con tu búsqueda.</p>
    <?php endif; ?>

    <div class="rejilla">
    <?php foreach ($productos as $p): ?>
        <?php
            $bajo = $p['stock_actual'] < $p['stock_minimo'];
            $tope = max($p['stock_minimo'] * 3, 1);
            $pct  = min(100, round($p['stock_actual'] / $tope * 100));

        
            $archivo = !empty($p['imagen']) ? basename($p['imagen']) : '';
            $ruta    = 'img/productos/' . $archivo;
            $hayFoto = $archivo !== '' && file_exists(__DIR__ . '/' . $ruta);
        ?>
        <article class="ficha <?= $bajo ? 'bajo' : '' ?>">
            <div class="foto">
                <?php if ($hayFoto): ?>
                    <img src="<?= e($ruta) ?>" alt="<?= e($p['nombre']) ?>">
                <?php else: ?>
                    <span aria-hidden="true"><?= e(mb_strtoupper(mb_substr($p['nombre'], 0, 1))) ?></span>
                <?php endif; ?>
            </div>

            <div class="cuerpo">
                <h2><?= e($p['nombre']) ?></h2>
                <div class="meta"><?= e($p['codigo']) ?> · <?= e($p['categoria']) ?></div>
                <p class="descripcion">
                    <?= $p['descripcion'] ? e($p['descripcion']) : 'Sin descripción.' ?>
                </p>

                <div class="pie">
                    <div class="precio">$<?= number_format($p['precio'], 0, ',', '.') ?></div>
                    <div class="nivel"><div style="width: <?= $pct ?>%"></div></div>
                    <div class="stock">
                        <span>Stock: <?= (int)$p['stock_actual'] ?> (mínimo <?= (int)$p['stock_minimo'] ?>)</span>
                        <?php if ($bajo): ?>
                            <span class="alerta">Stock bajo</span>
                        <?php endif; ?>
                    </div>

                    <div class="acciones">
                        <div class="izq">
                            <a href="producto_form.php?id=<?= (int)$p['id_producto'] ?>">Editar</a>
                            <a href="movimientos.php?producto=<?= (int)$p['id_producto'] ?>">Movimiento</a>
                        </div>
                        <form method="post" action="producto_desactivar.php"
                              onsubmit="return confirm('¿Desactivar este producto? Dejará de aparecer en el listado.');">
                            <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
                            <button type="submit" class="peligro">Desactivar</button>
                        </form>
                    </div>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</main>

</body>
</html>