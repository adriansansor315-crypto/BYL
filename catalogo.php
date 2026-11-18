<?php
require __DIR__ . '/inc/funciones.php';

$q = trim($_GET['q'] ?? '');
$cat = (int) ($_GET['categoria'] ?? 0);
$marca = (int) ($_GET['marca'] ?? 0);
$etiqueta = (int) ($_GET['etiqueta'] ?? 0);
$ofertas = !empty($_GET['ofertas']);
$orden = $_GET['orden'] ?? 'nuevo';
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$ordenes = ['nuevo' => 'p.id DESC', 'precio_asc' => 'precio_final ASC', 'precio_desc' => 'precio_final DESC',
            'nombre' => 'p.nombre ASC', 'calificacion' => 'promedio DESC, n_resenas DESC'];
if (!isset($ordenes[$orden])) {
    $orden = 'nuevo';
}

$donde = ['p.activo = 1'];
$par = [];
if ($q !== '') {
    $donde[] = '(p.nombre LIKE ? OR p.descripcion LIKE ? OR m.nombre LIKE ? OR p.sku LIKE ?)';
    $like = '%' . $q . '%';
    array_push($par, $like, $like, $like, $like);
}
if ($cat > 0) { $donde[] = 'p.categoria_id = ?'; $par[] = $cat; }
if ($marca > 0) { $donde[] = 'p.marca_id = ?'; $par[] = $marca; }
if ($etiqueta > 0) { $donde[] = 'p.id IN (SELECT producto_id FROM producto_etiqueta WHERE etiqueta_id = ?)'; $par[] = $etiqueta; }
if ($ofertas) { $donde[] = 'p.precio_oferta IS NOT NULL'; }
$where = ' WHERE ' . implode(' AND ', $donde);

$total = (int) fila('SELECT COUNT(*) AS n FROM productos p
    JOIN categorias c ON c.id = p.categoria_id LEFT JOIN marcas m ON m.id = p.marca_id' . $where, $par)['n'];
$paginas = max(1, (int) ceil($total / POR_PAGINA));
$pagina = min($pagina, $paginas);
$lista = filas(SQL_PRODUCTO . $where . ' ORDER BY ' . $ordenes[$orden] . ', p.id DESC LIMIT ? OFFSET ?',
    array_merge($par, [POR_PAGINA, ($pagina - 1) * POR_PAGINA]));

$categorias = filas('SELECT id, nombre FROM categorias ORDER BY nombre');
$marcas = filas('SELECT id, nombre FROM marcas ORDER BY nombre');
$etiquetas = filas('SELECT id, nombre FROM etiquetas ORDER BY nombre');
$deseados = Sesion::deseos();

$titulo = $ofertas ? 'Ofertas' : ($q !== '' ? 'Búsqueda: ' . $q : 'Catálogo');
require __DIR__ . '/inc/cabecera.php';
?>
<div class="container">
  <div class="catalogo-cabecera">
    <div>
      <h1 class="h3 mb-1"><?= e($ofertas ? 'Ofertas' : ($q !== '' ? 'Resultados para «' . $q . '»' : 'Catálogo')) ?></h1>
      <div class="text-muted"><?= $total ?> producto<?= $total === 1 ? '' : 's' ?></div>
    </div>
    <form method="get" class="orden-form">
      <?php foreach (['q' => $q, 'categoria' => $cat, 'marca' => $marca, 'etiqueta' => $etiqueta, 'ofertas' => $ofertas ? 1 : 0] as $k => $v): if ($v): ?>
        <input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>">
      <?php endif; endforeach; ?>
      <label for="orden" class="visually-hidden">Ordenar por</label>
      <select id="orden" name="orden" class="form-select form-select-sm">
        <option value="nuevo" <?= $orden === 'nuevo' ? 'selected' : '' ?>>Más nuevos</option>
        <option value="precio_asc" <?= $orden === 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
        <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
        <option value="calificacion" <?= $orden === 'calificacion' ? 'selected' : '' ?>>Mejor calificados</option>
        <option value="nombre" <?= $orden === 'nombre' ? 'selected' : '' ?>>Nombre A-Z</option>
      </select>
      <button class="btn btn-outline-byl btn-sm">Ordenar</button>
    </form>
  </div>

  <div class="row g-4">
    <aside class="col-lg-3">
      <div class="filtros">
        <h2 class="filtros-titulo">Categoría</h2>
        <a class="filtro <?= !$cat ? 'activo' : '' ?>" href="<?= e(url_catalogo(['categoria' => 0, 'pagina' => 1])) ?>">Todas las categorías</a>
        <?php foreach ($categorias as $c): ?>
          <a class="filtro <?= $cat === (int) $c['id'] ? 'activo' : '' ?>" href="<?= e(url_catalogo(['categoria' => (int) $c['id'], 'pagina' => 1])) ?>"><?= e($c['nombre']) ?></a>
        <?php endforeach; ?>
        <h2 class="filtros-titulo mt-3">Marca</h2>
        <a class="filtro <?= !$marca ? 'activo' : '' ?>" href="<?= e(url_catalogo(['marca' => 0, 'pagina' => 1])) ?>">Todas las marcas</a>
        <?php foreach ($marcas as $m): ?>
          <a class="filtro <?= $marca === (int) $m['id'] ? 'activo' : '' ?>" href="<?= e(url_catalogo(['marca' => (int) $m['id'], 'pagina' => 1])) ?>"><?= e($m['nombre']) ?></a>
        <?php endforeach; ?>
        <h2 class="filtros-titulo mt-3">Características</h2>
        <a class="filtro <?= !$etiqueta ? 'activo' : '' ?>" href="<?= e(url_catalogo(['etiqueta' => 0, 'pagina' => 1])) ?>">Todas las características</a>
        <?php foreach ($etiquetas as $t): ?>
          <a class="filtro <?= $etiqueta === (int) $t['id'] ? 'activo' : '' ?>" href="<?= e(url_catalogo(['etiqueta' => (int) $t['id'], 'pagina' => 1])) ?>"><?= e($t['nombre']) ?></a>
        <?php endforeach; ?>
        <a class="filtro filtro-oferta <?= $ofertas ? 'activo' : '' ?>" href="<?= e(url_catalogo(['ofertas' => $ofertas ? 0 : 1, 'pagina' => 1])) ?>"><?= icono('tag', 15) ?> Solo ofertas</a>
      </div>
    </aside>

    <section class="col-lg-9">
      <?php if (!$lista): ?>
        <div class="vacio">
          <?= icono('search', 40) ?>
          <h2 class="h5">No encontramos productos</h2>
          <p>Prueba con otra palabra o quita algún filtro.</p>
          <a class="btn btn-byl" href="<?= BASE ?>/catalogo.php">Ver todo el catálogo</a>
        </div>
      <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 g-3">
          <?php foreach ($lista as $p) tarjeta_producto($p, $deseados); ?>
        </div>
        <?php if ($paginas > 1): ?>
          <nav class="mt-4" aria-label="Páginas">
            <ul class="pagination justify-content-center flex-wrap">
              <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_catalogo(['pagina' => $pagina - 1])) ?>">Anterior</a></li>
              <?php for ($i = 1; $i <= $paginas; $i++): ?>
                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="<?= e(url_catalogo(['pagina' => $i])) ?>"><?= $i ?></a></li>
              <?php endfor; ?>
              <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_catalogo(['pagina' => $pagina + 1])) ?>">Siguiente</a></li>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require __DIR__ . '/inc/pie.php'; ?>
