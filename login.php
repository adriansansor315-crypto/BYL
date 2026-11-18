<?php
require __DIR__ . '/inc/funciones.php';
if (Sesion::usuario()) {
    redirigir('index.php');
}
$volver = ruta_segura($_REQUEST['volver'] ?? null);

if (es_post()) {
    validar_post('login.php');
    $acceso = trim($_POST['acceso'] ?? '');
    $clave = (string) ($_POST['clave'] ?? '');
    if (($_SESSION['bloqueo_hasta'] ?? 0) > time()) {
        mensaje('danger', 'Demasiados intentos. Espera un minuto e inténtalo otra vez.');
        redirigir('login.php?volver=' . urlencode($volver));
    }
    $usuario = null;
    if ($acceso !== '' && $clave !== '') {
        $usuario = fila('SELECT u.id, u.password_hash, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id
            WHERE u.activo = 1 AND r.nombre = ? AND (u.correo = ? OR u.usuario = ?)', ['cliente', mb_strtolower($acceso), $acceso]);
    }
    if ($usuario && password_verify($clave, $usuario['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $usuario['id'];
        unset($_SESSION['fallos'], $_SESSION['bloqueo_hasta']);
        redirigir($volver);
    }
    $_SESSION['fallos'] = ($_SESSION['fallos'] ?? 0) + 1;
    if ($_SESSION['fallos'] >= 5) {
        $_SESSION['bloqueo_hasta'] = time() + 60;
        $_SESSION['fallos'] = 0;
    }
    mensaje('danger', 'Usuario o contraseña incorrectos.');
    redirigir('login.php?volver=' . urlencode($volver));
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/inc/cabecera.php';
?>
<div class="container">
  <div class="caja-auth">
    <img class="auth-logo" src="<?= BASE ?>/imgs/logo.png" alt="Bella y Limpia" width="84" height="84">
    <h1 class="h3">Iniciar sesión</h1>
    <p class="text-muted">Entra para usar tu carrito, tus deseos y ver tus pedidos.</p>
    <form method="post" novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="volver" value="<?= e($volver) ?>">
      <div class="mb-3">
        <label for="acceso" class="form-label">Correo o usuario</label>
        <input id="acceso" name="acceso" class="form-control" autocomplete="username" maxlength="120" required>
      </div>
      <div class="mb-3">
        <label for="clave" class="form-label">Contraseña</label>
        <input id="clave" type="password" name="clave" class="form-control" autocomplete="current-password" maxlength="100" required>
      </div>
      <button class="btn btn-byl w-100 btn-lg">Entrar</button>
    </form>
    <p class="text-center mt-3 mb-0">¿Primera vez? <a href="<?= BASE ?>/registro.php">Crea tu cuenta</a></p>
  </div>
</div>
<?php require __DIR__ . '/inc/pie.php'; ?>
