<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
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
    <style>
        :root {
            --tinta:  #10283D;
            --papel:  #F3F6F8;
            --linea:  #BFCBD5;
            --texto:  #1B2B38;
            --suave:  #5A6B79;
            --accion: #0E6E78;
            --accion-oscuro: #0A545C;
            --ambar:  #F0A202;
            --ambar-fondo: #FFF3D6;
            --ambar-texto: #7A5200;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Instrument Sans', system-ui, sans-serif;
            color: var(--texto);
            background: var(--papel);
        }
        .barra {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 32px;
            background: var(--tinta);
            color: #fff;
        }
        .barra a { color: inherit; text-decoration: none; }
        .logo {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
        }
        .usuario { display: flex; align-items: center; gap: 16px; font-size: .95rem; }
        .salir {
            padding: 6px 12px;
            border: 1.5px solid rgba(255, 255, 255, .5);
            border-radius: 6px;
        }
        .salir:hover { background: rgba(255, 255, 255, .12); }

        main { max-width: 1180px; margin: 0 auto; padding: 32px; }
        h1 {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: -0.01em;
            margin: 0 0 4px;
        }
        .resumen { margin: 0 0 24px; color: var(--suave); }

        .buscador { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 28px; }
        .buscador input {
            flex: 1 1 280px;
            max-width: 460px;
            padding: 11px 14px;
            font: inherit;
            background: #fff;
            border: 1.5px solid var(--linea);
            border-radius: 6px;
        }
        .buscador input:focus,
        .buscador button:focus-visible,
        .buscador a:focus-visible {
            outline: 3px solid rgba(14, 110, 120, .35);
            outline-offset: 1px;
        }
        .buscador button {
            padding: 11px 20px;
            font: inherit;
            font-weight: 600;
            color: #fff;
            background: var(--accion);
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }
        .buscador button:hover { background: var(--accion-oscuro); }
        .buscador a {
            padding: 11px 16px;
            color: var(--accion);
            text-decoration: none;
            border-radius: 6px;
        }

        .rejilla {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 22px;
        }
        .ficha {
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1.5px solid var(--linea);
            border-radius: 8px;
            overflow: hidden;
        }
        .ficha.bajo { border-color: var(--ambar); }

        .foto {
            aspect-ratio: 4 / 3;
            background: #DDE6ED;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .foto img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .foto span {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 700;
            font-size: 3.5rem;
            color: var(--suave);
        }

        .cuerpo { display: flex; flex-direction: column; gap: 8px; padding: 16px; flex: 1; }
        .cuerpo h2 {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 600;
            font-size: 1.15rem;
            line-height: 1.25;
            margin: 0;
        }
        .meta { font-size: .85rem; color: var(--suave); }
        .descripcion {
            margin: 0;
            font-size: .93rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pie { margin-top: auto; padding-top: 10px; }
        .precio {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
        }
        .nivel {
            height: 6px;
            margin: 10px 0 8px;
            background: #E3EAF0;
            border-radius: 3px;
            overflow: hidden;
        }
        .nivel div { height: 100%; background: var(--accion); }
        .bajo .nivel div { background: var(--ambar); }
        .stock {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .88rem;
            color: var(--suave);
        }
        .alerta {
            padding: 3px 9px;
            font-weight: 600;
            font-size: .8rem;
            color: var(--ambar-texto);
            background: var(--ambar-fondo);
            border-radius: 4px;
        }
        .vacio { padding: 40px 0; color: var(--suave); }
.acciones { display: flex; justify-content: space-between; align-items: center;
            margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--linea); }
.acciones a { font-size: .9rem; font-weight: 600; color: var(--accion); text-decoration: none; }
.acciones a:hover { text-decoration: underline; }
.desactivar { font: inherit; font-size: .9rem; font-weight: 600; color: #A8231B;
              background: none; border: 0; cursor: pointer; }
.desactivar:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .barra { padding: 12px 16px; }
            main { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<header class="barra">
    <a class="logo" href="index.php">TecnoStock</a>
    <div class="usuario">
        <span>Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></span>
        <a class="salir" href="movimientos.php">Movimientos</a>
        <a class="salir" href="logout.php">Salir</a>
    </div>
</header>

<main>
    <h1>Productos</h1>
    <p class="resumen">
        <?= count($productos) ?> <?= count($productos) === 1 ? 'producto' : 'productos' ?>
        <?php if ($bajos > 0): ?>
            · <?= $bajos ?> con stock bajo
        <?php endif; ?>
    </p>

    <form class="buscador" method="get">
        <input type="text" name="buscar" placeholder="Buscar por código, nombre o categoría"
               aria-label="Buscar productos" value="<?= htmlspecialchars($buscar) ?>">
        <button type="submit">Buscar</button>
        <?php if ($buscar !== ''): ?>
            <a href="productos.php">Quitar filtro</a>
        <?php endif; ?>
        <a href="producto_form.php">+ Nuevo producto</a>
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

            // Imagen: solo se usa si el archivo existe en img/productos/
            $archivo = !empty($p['imagen']) ? basename($p['imagen']) : '';
            $ruta    = 'img/productos/' . $archivo;
            $hayFoto = $archivo !== '' && file_exists(__DIR__ . '/' . $ruta);
        ?>
        <article class="ficha <?= $bajo ? 'bajo' : '' ?>">
            <div class="foto">
                <?php if ($hayFoto): ?>
                    <img src="<?= htmlspecialchars($ruta) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
                <?php else: ?>
                    <span aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($p['nombre'], 0, 1))) ?></span>
                <?php endif; ?>
            </div>

            <div class="cuerpo">
                <h2><?= htmlspecialchars($p['nombre']) ?></h2>
                <div class="meta"><?= htmlspecialchars($p['codigo']) ?> · <?= htmlspecialchars($p['categoria']) ?></div>
                <p class="descripcion">
                    <?= $p['descripcion'] ? htmlspecialchars($p['descripcion']) : 'Sin descripción.' ?>
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
                        <a href="producto_form.php?id=<?= (int)$p['id_producto'] ?>">Editar</a>
                        <form method="post" action="producto_desactivar.php"
      onsubmit="return confirm('¿Desactivar este producto?');">
    <input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>">
    <button type="submit" class="desactivar">Desactivar</button>
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