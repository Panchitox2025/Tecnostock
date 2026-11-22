<?php
session_start();
require 'conexion.php';

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '') {
        $error = 'Completa el correo y la contraseña.';
    } else {
        $stmt = $pdo->prepare('SELECT id_usuario, nombre, clave FROM usuario WHERE correo = ? AND estado = 1');
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($clave, $usuario['clave'])) {
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre'];
            header('Location: index.php');
            exit;
        }
        $error = 'El correo o la contraseña no coinciden. Revísalos e inténtalo de nuevo.';
    }
}

// Niveles de stock de las casillas decorativas (menos de 20 = stock bajo)
$niveles = [80, 55, 90, 35, 70, 60, 15, 85, 50, 75, 40, 65];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Iniciar sesión</title>
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
            --error:  #A8231B;
            --error-fondo: #FCECEA;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Instrument Sans', system-ui, sans-serif;
            color: var(--texto);
            background: var(--papel);
        }
        .pantalla {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            min-height: 100vh;
        }

        /* ---------- Panel izquierdo ---------- */
        .marca {
            background: var(--tinta);
            color: #fff;
            padding: 56px 64px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 40px;
        }
        .marca h1 {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 700;
            font-size: clamp(2.2rem, 4vw, 3.4rem);
            line-height: 1.05;
            letter-spacing: -0.02em;
            margin: 0 0 16px;
            max-width: 11ch;
        }
        .marca p {
            margin: 0;
            max-width: 38ch;
            color: rgba(255, 255, 255, .75);
            line-height: 1.55;
        }
        .estante {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            max-width: 340px;
        }
        .casilla {
            aspect-ratio: 1;
            position: relative;
            overflow: hidden;
            border: 1.5px solid rgba(255, 255, 255, .3);
            border-radius: 4px;
        }
        .casilla::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: var(--nivel);
            background: rgba(255, 255, 255, .22);
        }
        .casilla.bajo { border-color: var(--ambar); }
        .casilla.bajo::after { background: var(--ambar); }
        .leyenda {
            margin-top: 14px;
            font-size: .85rem;
            color: rgba(255, 255, 255, .65);
        }

        /* ---------- Panel derecho ---------- */
        .acceso {
            display: flex;
            flex-direction: column;
            padding: 40px 56px;
        }
        .logo {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--tinta);
        }
        .formulario {
            width: 100%;
            max-width: 360px;
            margin: auto 0;
            padding: 32px 0;
        }
        .formulario h2 {
            font-family: 'Bricolage Grotesque', system-ui, sans-serif;
            font-weight: 600;
            font-size: 1.9rem;
            letter-spacing: -0.01em;
            margin: 0 0 6px;
        }
        .formulario .intro {
            margin: 0 0 28px;
            color: var(--suave);
        }
        .campo { margin-bottom: 18px; }
        .campo label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .campo input {
            width: 100%;
            padding: 12px 14px;
            font: inherit;
            color: var(--texto);
            background: #fff;
            border: 1.5px solid var(--linea);
            border-radius: 6px;
        }
        .campo input:focus {
            outline: 3px solid rgba(14, 110, 120, .35);
            outline-offset: 1px;
            border-color: var(--accion);
        }
        .con-boton { position: relative; }
        .con-boton input { padding-right: 84px; }
        .ver {
            position: absolute;
            right: 6px; top: 50%;
            transform: translateY(-50%);
            padding: 6px 10px;
            font: inherit;
            font-size: .9rem;
            color: var(--accion);
            background: none;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
        }
        .ver:focus-visible { outline: 3px solid rgba(14, 110, 120, .35); }
        .entrar {
            width: 100%;
            margin-top: 6px;
            padding: 13px;
            font: inherit;
            font-weight: 600;
            color: #fff;
            background: var(--accion);
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }
        .entrar:hover { background: var(--accion-oscuro); }
        .entrar:focus-visible { outline: 3px solid rgba(14, 110, 120, .45); outline-offset: 2px; }
        .error {
            margin-bottom: 20px;
            padding: 12px 14px;
            color: var(--error);
            background: var(--error-fondo);
            border-left: 4px solid var(--error);
            border-radius: 4px;
            font-size: .95rem;
        }

        /* ---------- Celular ---------- */
        @media (max-width: 800px) {
            .pantalla { grid-template-columns: 1fr; }
            .marca { padding: 32px 24px; gap: 24px; }
            .marca h1 { max-width: none; }
            .estante { grid-template-columns: repeat(6, 1fr); max-width: none; }
            .acceso { padding: 24px; }
            .formulario { margin: 0; }
        }
    </style>
</head>
<body>
<div class="pantalla">

    <section class="marca">
        <div>
            <h1>Tu inventario, siempre al día</h1>
            <p>Registra entradas y salidas de productos y mira qué se está agotando antes de quedarte sin stock.</p>
        </div>
        <div>
            <div class="estante" aria-hidden="true">
                <?php foreach ($niveles as $n): ?>
                    <div class="casilla <?= $n < 20 ? 'bajo' : '' ?>" style="--nivel: <?= $n ?>%"></div>
                <?php endforeach; ?>
            </div>
            <p class="leyenda">Cada casilla es un producto. La ámbar tiene stock bajo.</p>
        </div>
    </section>

    <main class="acceso">
        <div class="logo">TecnoStock</div>

        <form class="formulario" method="post" novalidate>
            <h2>Iniciar sesión</h2>
            <p class="intro">Ingresa con tu correo y tu contraseña.</p>

            <?php if ($error): ?>
                <div class="error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" autocomplete="username"
                       value="<?= htmlspecialchars($correo) ?>" required autofocus>
            </div>

            <div class="campo">
                <label for="clave">Contraseña</label>
                <div class="con-boton">
                    <input type="password" id="clave" name="clave" autocomplete="current-password" required>
                    <button type="button" class="ver" id="ver" aria-controls="clave">Mostrar</button>
                </div>
            </div>

            <button class="entrar" type="submit">Ingresar</button>
        </form>
    </main>

</div>

<script>
    // Botón para mostrar u ocultar la contraseña

    const clave = document.getElementById('clave');
    const ver   = document.getElementById('ver');
    ver.addEventListener('click', () => {
        const oculta = clave.type === 'password';
        clave.type = oculta ? 'text' : 'password';
        ver.textContent = oculta ? 'Ocultar' : 'Mostrar';
    });
    const form = document.querySelector('.formulario');
form.addEventListener('submit', (e) => {
    const correo = document.getElementById('correo').value.trim();
    const pass   = document.getElementById('clave').value;
    let msg = '';

    if (correo === '' || pass === '') {
        msg = 'Completa el correo y la contraseña.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
        msg = 'Escribe un correo válido.';
    }

    if (msg) {
        e.preventDefault();
        let caja = document.querySelector('.error');
        if (!caja) {
            caja = document.createElement('div');
            caja.className = 'error';
            caja.setAttribute('role', 'alert');
            form.querySelector('.intro').after(caja);
        }
        caja.textContent = msg;
    }
});
</script>
</body>
</html>