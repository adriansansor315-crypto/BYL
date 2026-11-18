<?php
$u = Sesion::usuario();
$cats = filas('SELECT id, nombre FROM categorias ORDER BY nombre');
$n_carrito = $u ? (int) fila('SELECT COALESCE(SUM(cantidad), 0) AS n FROM carrito_items WHERE usuario_id = ?', [$u['id']])['n'] : 0;
$n_deseos = $u ? count(Sesion::deseos()) : 0;
$q_actual = trim($_GET['q'] ?? '');
$cat_actual = (int) ($_GET['categoria'] ?? 0);
$es_catalogo = basename($_SERVER['SCRIPT_NAME']) === 'catalogo.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(($titulo ?? '') !== '' ? $titulo . ' · ' . TIENDA : TIENDA) ?></title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/imgs/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="<?= BASE ?>/css/estilos.css" rel="stylesheet">
</head>
<body>

<div class="franja-promo">
  <?= icono('truck', 16) ?> Recoge en tienda sin costo · Cada producto te dice <strong>para qué sirve</strong>
</div>

<header class="encabezado">
  <div class="container encabezado-fila">
    <a class="logo" href="<?= BASE ?>/index.php" aria-label="<?= e(TIENDA) ?>, inicio">
      <img class="logo-img" src="<?= BASE ?>/imgs/logo.png" alt="" width="46" height="46">
      <span>Bella <em>y</em> Limpia</span>
    </a>

    <form class="buscador" action="<?= BASE ?>/catalogo.php" method="get" role="search">
      <input type="search" name="q" value="<?= e($q_actual) ?>" placeholder="Busca labial, crema, shampoo…" aria-label="Buscar productos" maxlength="60">
      <button aria-label="Buscar"><?= icono('search', 18) ?></button>
    </form>

    <nav class="acciones" aria-label="Cuenta">
      <a class="accion" href="<?= BASE ?>/deseos.php" title="Mis deseos">
        <?= icono('heart', 22) ?>
        <?php if ($n_deseos > 0): ?><span class="contador"><?= $n_deseos ?></span><?php endif; ?>
        <span class="accion-texto">Deseos</span>
      </a>
      <a class="accion" href="<?= BASE ?>/carrito.php" title="Mi carrito">
        <?= icono('cart', 22) ?>
        <?php if ($n_carrito > 0): ?><span class="contador"><?= $n_carrito ?></span><?php endif; ?>
        <span class="accion-texto">Carrito</span>
      </a>
      <?php if ($u): ?>
        <details class="menu-usuario">
          <summary class="accion"><?= icono('user', 22) ?><span class="accion-texto"><?= e(explode(' ', $u['nombre'])[0]) ?></span></summary>
          <div class="menu-caja">
            <a href="<?= BASE ?>/cuenta.php">Mi cuenta</a>
            <a href="<?= BASE ?>/mis_pedidos.php">Mis pedidos</a>
            <form method="post" action="<?= BASE ?>/salir.php"><?= csrf_campo() ?><button>Cerrar sesión</button></form>
          </div>
        </details>
      <?php else: ?>
        <a class="accion" href="<?= BASE ?>/login.php" title="Iniciar sesión">
          <?= icono('user', 22) ?><span class="accion-texto">Ingresar</span>
        </a>
      <?php endif; ?>
    </nav>
  </div>

  <nav class="categorias" aria-label="Categorías">
    <div class="container categorias-fila">
      <a href="<?= BASE ?>/catalogo.php" class="<?= $es_catalogo && !$cat_actual && empty($_GET['ofertas']) ? 'activa' : '' ?>">Todo</a>
      <a href="<?= BASE ?>/catalogo.php?ofertas=1" class="ofertas <?= $es_catalogo && !empty($_GET['ofertas']) ? 'activa' : '' ?>"><?= icono('tag', 15) ?> Ofertas</a>
      <?php foreach ($cats as $c): ?>
        <a href="<?= BASE ?>/catalogo.php?categoria=<?= (int) $c['id'] ?>" class="<?= $es_catalogo && $cat_actual === (int) $c['id'] ? 'activa' : '' ?>"><?= e($c['nombre']) ?></a>
      <?php endforeach; ?>
    </div>
  </nav>
</header>

<main class="contenido">
  <div class="container">
    <?php mostrar_mensajes(); ?>
  </div>
