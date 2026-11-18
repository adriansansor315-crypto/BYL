<?php
require __DIR__ . '/../inc/funciones.php';
$actual = Sesion::usuario();
if ($actual && $actual['rol'] !== 'cliente') {
    redirigir('admin/index.php');
}
$volver = ruta_segura($_REQUEST['volver'] ?? null, 'admin/index.php');

if (es_post()) {
    validar_post('admin/acceso.php');
    $acceso = trim($_POST['acceso'] ?? '');
    $clave = (string) ($_POST['clave'] ?? '');
    if (($_SESSION['bloqueo_hasta'] ?? 0) > time()) {
        mensaje('danger', 'Demasiados intentos. Espera un minuto e inténtalo otra vez.');
        redirigir('admin/acceso.php');
    }
    $cuenta = null;
    if ($acceso !== '' && $clave !== '') {
        $cuenta = fila('SELECT u.id, u.password_hash, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id
            WHERE u.activo = 1 AND (u.correo = ? OR u.usuario = ?)', [mb_strtolower($acceso), $acceso]);
    }
    $permitido = $cuenta && $cuenta['rol'] !== 'cliente' && ($cuenta['rol'] !== 'sistemas' || !SISTEMAS_SOLO_LOCAL || Sesion::esLocal());
    if ($permitido && password_verify($clave, $cuenta['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $cuenta['id'];
        unset($_SESSION['fallos'], $_SESSION['bloqueo_hasta']);
        redirigir($volver);
    }
    $_SESSION['fallos'] = ($_SESSION['fallos'] ?? 0) + 1;
    if ($_SESSION['fallos'] >= 5) {
        $_SESSION['bloqueo_hasta'] = time() + 60;
        $_SESSION['fallos'] = 0;
    }
    mensaje('danger', 'Usuario o contraseña incorrectos.');
    redirigir('admin/acceso.php?volver=' . urlencode($volver));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Acceso del personal · <?= e(TIENDA) ?></title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/imgs/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="<?= BASE ?>/css/estilos.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
  <div class="caja-auth">
    <img class="auth-logo" src="<?= BASE ?>/imgs/logo.png" alt="Bella y Limpia" width="84" height="84">
    <h1 class="h3">Acceso del personal</h1>
    <p class="text-muted">Solo para la administradora y el área de sistemas.</p>
    <?php mostrar_mensajes(); ?>
    <form method="post" novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="volver" value="<?= e($volver) ?>">
      <div class="mb-3">
        <label for="acceso" class="form-label">Usuario o correo</label>
        <input id="acceso" name="acceso" class="form-control" autocomplete="username" maxlength="120" required>
      </div>
      <div class="mb-3">
        <label for="clave" class="form-label">Contraseña</label>
        <input id="clave" type="password" name="clave" class="form-control" autocomplete="current-password" maxlength="100" required>
      </div>
      <button class="btn btn-byl w-100 btn-lg">Entrar</button>
    </form>
  </div>
</div>
</body>
</html>
