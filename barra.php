<?php
$pagina = $pagina ?? '';
?>
<header class="barra">
    <a class="logo" href="productos.php">TecnoStock</a>
    <nav class="usuario">
        <a class="enlace <?= $pagina === 'productos' ? 'activo' : '' ?>" href="productos.php">Productos</a>
        <a class="enlace <?= $pagina === 'movimientos' ? 'activo' : '' ?>" href="movimientos.php">Movimientos</a>
        <span>Hola, <?= e($_SESSION['nombre']) ?></span>
        <a class="salir" href="logout.php">Salir</a>
    </nav>
</header>
