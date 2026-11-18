<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora']);
$titulo = 'Productos';
$seccion = 'productos';

if (es_post()) {
    validar_post('admin/productos.php');
    $id = (int) ($_POST['id'] ?? 0);
    $p = fila('SELECT id, nombre, imagen, activo FROM productos WHERE id = ?', [$id]);
    if (!$p) {
        mensaje('danger', 'Ese producto no existe.');
        redirigir('admin/productos.php');
    }
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'alternar') {
        ejecuta('UPDATE productos SET activo = ? WHERE id = ?', [$p['activo'] ? 0 : 1, $id]);
        mensaje('info', '«' . $p['nombre'] . '» ahora está ' . ($p['activo'] ? 'oculto.' : 'visible.'));
    } elseif ($accion === 'borrar') {
        if (fila('SELECT 1 AS x FROM pedido_detalle WHERE producto_id = ?', [$id])) {
            ejecuta('UPDATE productos SET activo = 0 WHERE id = ?', [$id]);
            mensaje('warning', '«' . $p['nombre'] . '» ya aparece en pedidos, así que no se puede borrar. Lo ocultamos de la tienda.');
        } else {
            ejecuta('DELETE FROM productos WHERE id = ?', [$id]);
            Foto::borrar($p['imagen']);
            mensaje('success', 'Borramos «' . $p['nombre'] . '».');
        }
    }
    redirigir(ruta_segura($_POST['volver_lista'] ?? null, 'admin/productos.php'));
}

$q = trim($_GET['q'] ?? '');
$filtro = $_GET['filtro'] ?? '';
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$por_pagina = 25;
$donde = ['1 = 1'];
$par = [];
if ($q !== '') {
    $donde[] = '(p.nombre LIKE ? OR m.nombre LIKE ? OR c.nombre LIKE ? OR p.sku LIKE ?)';
    $like = '%' . $q . '%';
    array_push($par, $like, $like, $like, $like);
}
if ($filtro === 'agotados') $donde[] = 'p.stock = 0';
if ($filtro === 'bajos') { $donde[] = 'p.stock <= ?'; $par[] = STOCK_BAJO; }
if ($filtro === 'ocultos') $donde[] = 'p.activo = 0';
if ($filtro === 'ofertas') $donde[] = 'p.precio_oferta IS NOT NULL';
$where = ' WHERE ' . implode(' AND ', $donde);

$total = (int) fila('SELECT COUNT(*) AS n FROM productos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN marcas m ON m.id = p.marca_id' . $where, $par)['n'];
$paginas = max(1, (int) ceil($total / $por_pagina));
$pagina = min($pagina, $paginas);
$lista = filas(SQL_PRODUCTO . $where . ' ORDER BY p.id DESC LIMIT ? OFFSET ?', array_merge($par, [$por_pagina, ($pagina - 1) * $por_pagina]));
$volver_lista = ruta_actual();

function enlace_pagina(int $n): string
{
    $q = $_GET;
    $q['pagina'] = $n;
    return BASE . '/admin/productos.php?' . http_build_query($q);
}

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 mb-0">Productos <span class="text-muted fs-6">(<?= $total ?>)</span></h1>
  <a class="btn btn-byl" href="<?= BASE ?>/admin/producto.php"><?= icono('plus', 18) ?> Nuevo producto</a>
</div>

<form class="barra-filtros" method="get">
  <label for="q" class="visually-hidden">Buscar</label>
  <input id="q" type="search" name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Buscar por nombre, código, marca o categoría">
  <label for="filtro" class="visually-hidden">Filtro</label>
  <select id="filtro" name="filtro" class="form-select form-select-sm">
    <?php foreach (['' => 'Todos', 'agotados' => 'Agotados', 'bajos' => 'Pocas piezas', 'ofertas' => 'En oferta', 'ocultos' => 'Ocultos'] as $k => $t): ?>
      <option value="<?= $k ?>" <?= $filtro === (string) $k ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-outline-byl btn-sm">Buscar</button>
</form>

<div class="table-responsive panel-caja p-0">
  <table class="table tabla-byl align-middle mb-0">
    <thead><tr><th></th><th>Producto</th><th>Código</th><th>Categoría</th><th>Marca</th><th class="text-end">Precio</th><th class="text-end">Piezas</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lista as $p): ?>
      <tr class="<?= $p['activo'] ? '' : 'fila-oculta' ?>">
        <td class="td-mini"><?= imagen_producto($p, 'mini') ?></td>
        <td><strong><?= e($p['nombre']) ?></strong></td>
        <td class="text-muted small"><?= e($p['sku']) ?></td>
        <td><?= e($p['categoria']) ?></td>
        <td><?= e($p['marca'] ?? '—') ?></td>
        <td class="text-end">
          <?= dinero($p['precio_final']) ?>
          <?php if ($p['precio_oferta'] !== null): ?><br><small class="precio-viejo"><?= dinero($p['precio']) ?></small><?php endif; ?>
        </td>
        <td class="text-end"><span class="badge text-bg-<?= (int) $p['stock'] === 0 ? 'danger' : ((int) $p['stock'] <= STOCK_BAJO ? 'warning' : 'light border') ?>"><?= (int) $p['stock'] ?></span></td>
        <td><?= $p['activo'] ? '<span class="badge text-bg-success">Visible</span>' : '<span class="badge text-bg-secondary">Oculto</span>' ?></td>
        <td class="text-end acciones-fila">
          <a class="btn btn-outline-byl btn-sm" href="<?= BASE ?>/admin/producto.php?id=<?= (int) $p['id'] ?>" title="Editar"><?= icono('edit', 15) ?></a>
          <form method="post" class="d-inline">
            <?= csrf_campo() ?><input type="hidden" name="accion" value="alternar"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <input type="hidden" name="volver_lista" value="<?= e($volver_lista) ?>">
            <button class="btn btn-outline-secondary btn-sm" title="<?= $p['activo'] ? 'Ocultar' : 'Mostrar' ?>"><?= icono($p['activo'] ? 'eye-off' : 'eye', 15) ?></button>
          </form>
          <?php boton_confirmar(icono('trash', 15), '¿Borrar «' . $p['nombre'] . '»?', ['accion' => 'borrar', 'id' => $p['id'], 'volver_lista' => $volver_lista], 'btn btn-outline-danger btn-sm', 'Sí, borrar'); ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="9" class="text-center text-muted py-4">No hay productos con ese filtro.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($paginas > 1): ?>
<nav class="mt-3" aria-label="Páginas">
  <ul class="pagination justify-content-center flex-wrap">
    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(enlace_pagina($pagina - 1)) ?>">Anterior</a></li>
    <?php for ($i = 1; $i <= $paginas; $i++): ?>
      <li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="<?= e(enlace_pagina($i)) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
    <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(enlace_pagina($pagina + 1)) ?>">Siguiente</a></li>
  </ul>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
