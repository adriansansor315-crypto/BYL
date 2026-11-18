<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora', 'sistemas']);
$titulo = 'Reportes';
$seccion = 'reportes';

$hoy = date('Y-m-d');
$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-60 days'));
$hasta = $_GET['hasta'] ?? $hoy;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-d', strtotime('-60 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = $hoy;
if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

$por_dia = llama('sp_resumen_ventas', [$desde, $hasta]);
$tot_pedidos = array_sum(array_column($por_dia, 'pedidos'));
$tot_vendido = array_sum(array_column($por_dia, 'vendido'));
$tot_desc = array_sum(array_column($por_dia, 'descontado'));
$por_categoria = filas("SELECT c.nombre, SUM(d.cantidad) AS piezas, SUM(d.cantidad * d.precio_unitario) AS importe
    FROM pedido_detalle d JOIN pedidos pe ON pe.id = d.pedido_id JOIN productos pr ON pr.id = d.producto_id
    JOIN categorias c ON c.id = pr.categoria_id WHERE pe.estado <> 'cancelado' AND DATE(pe.creado_en) BETWEEN ? AND ?
    GROUP BY c.id, c.nombre ORDER BY importe DESC", [$desde, $hasta]);
$top = filas("SELECT pr.nombre, SUM(d.cantidad) AS piezas, SUM(d.cantidad * d.precio_unitario) AS importe
    FROM pedido_detalle d JOIN pedidos pe ON pe.id = d.pedido_id JOIN productos pr ON pr.id = d.producto_id
    WHERE pe.estado <> 'cancelado' AND DATE(pe.creado_en) BETWEEN ? AND ?
    GROUP BY pr.id, pr.nombre ORDER BY piezas DESC, importe DESC LIMIT 8", [$desde, $hasta]);
$clientes = filas("SELECT u.nombre, COUNT(*) AS pedidos, SUM(pe.total) AS gastado
    FROM pedidos pe JOIN usuarios u ON u.id = pe.usuario_id
    WHERE pe.estado <> 'cancelado' AND DATE(pe.creado_en) BETWEEN ? AND ?
    GROUP BY u.id, u.nombre ORDER BY gastado DESC LIMIT 5", [$desde, $hasta]);
$estados = filas("SELECT estado, COUNT(*) AS n FROM pedidos WHERE DATE(creado_en) BETWEEN ? AND ? GROUP BY estado ORDER BY n DESC", [$desde, $hasta]);
$bajos = llama('sp_bajo_stock', [STOCK_BAJO]);
$max_cat = $por_categoria ? max(array_column($por_categoria, 'importe')) : 1;

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-3">Reportes</h1>
<form method="get" class="barra-filtros align-items-end">
  <div><label for="desde" class="form-label mb-1">Desde</label><input id="desde" type="date" name="desde" class="form-control" value="<?= e($desde) ?>" max="<?= $hoy ?>"></div>
  <div><label for="hasta" class="form-label mb-1">Hasta</label><input id="hasta" type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>" max="<?= $hoy ?>"></div>
  <button class="btn btn-byl">Consultar</button>
</form>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat"><span>Pedidos (sin cancelados)</span><strong><?= (int) $tot_pedidos ?></strong></div></div>
  <div class="col-md-4"><div class="stat"><span>Ventas</span><strong><?= dinero($tot_vendido) ?></strong></div></div>
  <div class="col-md-4"><div class="stat"><span>Descuentos dados</span><strong><?= dinero($tot_desc) ?></strong></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel-caja">
      <h2 class="h5">Ventas por día</h2>
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>Día</th><th class="text-end">Pedidos</th><th class="text-end">Vendido</th></tr></thead>
        <tbody>
        <?php foreach ($por_dia as $d): ?>
          <tr><td><?= e(date('d/m/Y', strtotime($d['dia']))) ?></td><td class="text-end"><?= (int) $d['pedidos'] ?></td><td class="text-end"><?= dinero($d['vendido']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$por_dia): ?><tr><td colspan="3" class="text-muted text-center py-3">Sin ventas en este periodo.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-caja">
      <h2 class="h5">Pedidos por estado</h2>
      <?php foreach ($estados as $es): ?>
        <div class="resumen-fila"><span><?= etiqueta_estado($es['estado']) ?></span><strong><?= (int) $es['n'] ?></strong></div>
      <?php endforeach; ?>
      <?php if (!$estados): ?><p class="text-muted mb-0">Sin pedidos en este periodo.</p><?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel-caja">
      <h2 class="h5">Ventas por categoría</h2>
      <?php foreach ($por_categoria as $c): ?>
        <div class="barra-fila"><span><?= e($c['nombre']) ?></span><div class="barra"><div style="width:<?= max(3, round(100 * (float) $c['importe'] / (float) $max_cat)) ?>%"></div></div><strong><?= dinero($c['importe']) ?></strong></div>
      <?php endforeach; ?>
      <?php if (!$por_categoria): ?><p class="text-muted mb-0">Sin ventas en este periodo.</p><?php endif; ?>
    </div>
    <div class="panel-caja">
      <h2 class="h5">Productos más vendidos</h2>
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>Producto</th><th class="text-end">Piezas</th><th class="text-end">Importe</th></tr></thead>
        <tbody>
        <?php foreach ($top as $t): ?>
          <tr><td><?= e($t['nombre']) ?></td><td class="text-end"><?= (int) $t['piezas'] ?></td><td class="text-end"><?= dinero($t['importe']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-caja">
      <h2 class="h5">Mejores clientas</h2>
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>Nombre</th><th class="text-end">Pedidos</th><th class="text-end">Gastado</th></tr></thead>
        <tbody>
        <?php foreach ($clientes as $c): ?>
          <tr><td><?= e($c['nombre']) ?></td><td class="text-end"><?= (int) $c['pedidos'] ?></td><td class="text-end"><?= dinero($c['gastado']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-12">
    <div class="panel-caja">
      <h2 class="h5">Existencias bajas (<?= (int) STOCK_BAJO ?> piezas o menos)</h2>
      <?php if (!$bajos): ?><p class="text-muted mb-0">Todo tiene existencias suficientes.</p><?php else: ?>
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>SKU</th><th>Producto</th><th class="text-end">Piezas</th></tr></thead>
        <tbody>
        <?php foreach ($bajos as $b): ?>
          <tr><td><?= e($b['sku']) ?></td><td><?= e($b['nombre']) ?></td><td class="text-end"><?= (int) $b['stock'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
