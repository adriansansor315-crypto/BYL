<?php
$u = Sesion::usuario();
$rol = $u['rol'];
$pendientes = (int) fila("SELECT COUNT(*) AS n FROM pedidos WHERE estado = 'pendiente'")['n'];
$todo = [
    'resumen'    => ['index.php', 'home', 'Inicio', ['administradora', 'sistemas']],
    'alta'       => ['producto.php', 'plus', 'Registrar producto', ['administradora']],
    'productos'  => ['productos.php', 'box', 'Consultar productos', ['administradora']],
    'importar'   => ['importar.php', 'upload', 'Importar CSV', ['administradora']],
    'catalogos'  => ['catalogos.php', 'tag', 'Catálogos', ['administradora']],
    'inventario' => ['inventario.php', 'stock', 'Inventario', ['administradora']],
    'pedidos'    => ['pedidos.php', 'list', 'Pedidos', ['administradora']],
    'cuentas'    => ['cuentas.php', 'user', 'Cuentas', ['administradora', 'sistemas']],
    'promos'     => ['promociones.php', 'star', 'Promociones', ['administradora']],
    'cupones'    => ['cupones.php', 'coupon', 'Cupones', ['administradora']],
    'resenas'    => ['resenas.php', 'chat', 'Reseñas', ['administradora']],
    'reportes'   => ['reportes.php', 'stock', 'Reportes', ['administradora', 'sistemas']],
    'respaldo'   => ['respaldo.php', 'upload', 'Respaldo', ['sistemas']],
];
$menu = [];
foreach ($todo as $clave => [$archivo, $ico, $texto, $roles]) {
    if (in_array($rol, $roles, true)) {
        $menu[$clave] = [$archivo, $ico, $texto];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Panel') ?> · Panel · <?= e(TIENDA) ?></title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/imgs/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="<?= BASE ?>/css/estilos.css" rel="stylesheet">
</head>
<body class="admin">
<div id="panel" class="panel">
  <div id="panel-encabezado" class="panel-encabezado">
    <a class="admin-logo" href="<?= BASE ?>/admin/index.php"><img src="<?= BASE ?>/imgs/logo.png" alt="" width="34" height="34"> <span>BYL · <?= $rol === 'sistemas' ? 'Panel de sistemas' : 'Panel de administración' ?></span></a>
    <div class="admin-der">
      <span class="d-none d-md-inline"><?= e($u['nombre']) ?></span>
      <a href="<?= BASE ?>/index.php" target="_blank" rel="noopener">Ver tienda</a>
    </div>
  </div>
  <div class="panel-cuerpo">
    <div id="panel-menu" class="panel-menu">
      <nav aria-label="Panel">
        <?php foreach ($menu as $clave => [$archivo, $ico, $texto]): ?>
          <a href="<?= BASE ?>/admin/<?= $archivo ?>" class="<?= ($seccion ?? '') === $clave ? 'activo' : '' ?>">
            <?= icono($ico, 18) ?> <span><?= e($texto) ?></span>
            <?php if ($clave === 'pedidos' && $pendientes > 0): ?><span class="contador contador-estatico"><?= $pendientes ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
        <form method="post" action="<?= BASE ?>/salir.php"><?= csrf_campo() ?><button class="btn-enlace menu-salir"><?= icono('user', 18) ?> <span>Salir</span></button></form>
      </nav>
    </div>
    <div id="panel-contenido" class="panel-contenido">
      <main>
        <?php mostrar_mensajes(); ?>
