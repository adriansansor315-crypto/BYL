<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora', 'sistemas']);
$titulo = 'Resumen';
$seccion = 'resumen';

$n_activos = (int) fila('SELECT COUNT(*) AS n FROM productos WHERE activo = 1')['n'];
$n_agotados = (int) fila('SELECT COUNT(*) AS n FROM productos WHERE activo = 1 AND stock = 0')['n'];
$n_pend = (int) fila("SELECT COUNT(*) AS n FROM pedidos WHERE estado = 'pendiente'")['n'];
$vendido = (float) fila("SELECT COALESCE(SUM(total), 0) AS t FROM pedidos WHERE estado = 'entregado'")['t'];
$n_clientes = (int) fila("SELECT COUNT(*) AS n FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE r.nombre = 'cliente'")['n'];
$valor_inv = (float) fila('SELECT COALESCE(SUM(stock * precio), 0) AS t FROM productos WHERE activo = 1')['t'];
$bajos = array_slice(llama('sp_bajo_stock', [STOCK_BAJO]), 0, 8);
$ventas_dias = llama('sp_resumen_ventas', [date('Y-m-d', strtotime('-13 days')), date('Y-m-d')]);
$max_dia = $ventas_dias ? max(array_column($ventas_dias, 'vendido')) : 1;
$ultimos = filas('SELECT p.id, p.estado, p.total, p.creado_en, u.nombre FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id DESC LIMIT 6');
$top = filas("SELECT pr.id, pr.nombre, SUM(d.cantidad) AS piezas, SUM(d.cantidad * d.precio_unitario) AS importe
    FROM pedido_detalle d JOIN pedidos pe ON pe.id = d.pedido_id JOIN productos pr ON pr.id = d.producto_id
    WHERE pe.estado <> 'cancelado' GROUP BY pr.id, pr.nombre ORDER BY piezas DESC, importe DESC LIMIT 6");
$por_categoria = filas("SELECT c.nombre, SUM(d.cantidad * d.precio_unitario) AS importe
    FROM pedido_detalle d JOIN pedidos pe ON pe.id = d.pedido_id JOIN productos pr ON pr.id = d.producto_id
    JOIN categorias c ON c.id = pr.categoria_id WHERE pe.estado <> 'cancelado' GROUP BY c.id, c.nombre ORDER BY importe DESC");
$max_cat = $por_categoria ? max(array_column($por_categoria, 'importe')) : 1;

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-4">Resumen</h1>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4 col-xxl-2"><div class="stat"><span>Productos activos</span><strong><?= $n_activos ?></strong></div></div>
  <div class="col-6 col-md-4 col-xxl-2"><div class="stat <?= $n_agotados ? 'stat-alerta' : '' ?>"><span>Agotados</span><strong><?= $n_agotados ?></strong></div></div>
  <div class="col-6 col-md-4 col-xxl-2"><div class="stat <?= $n_pend ? 'stat-alerta' : '' ?>"><span>Pedidos pendientes</span><strong><?= $n_pend ?></strong></div></div>
  <div class="col-6 col-md-4 col-xxl-2"><div class="stat"><span>Clientes</span><strong><?= $n_clientes ?></strong></div></div>
  <div class="col-6 col-md-4 col-xxl-2"><div class="stat"><span>Vendido (entregado)</span><strong><?= dinero($vendido) ?></strong></div></div>
  <div class="col-6 col-md-4 col-xxl-2"><div class="stat"><span>Valor del inventario</span><strong><?= dinero($valor_inv) ?></strong></div></div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel-caja">
      <h2 class="h5">Hay que resurtir</h2>
      <?php if (!$bajos): ?><p class="text-muted mb-0">Todo tiene existencias suficientes.</p>
      <?php else: ?>
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>Producto</th><th class="text-end">Piezas</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bajos as $b): ?>
          <tr><td><?= e($b['nombre']) ?></td>
              <td class="text-end"><span class="badge text-bg-<?= (int) $b['stock'] === 0 ? 'danger' : 'warning' ?>"><?= (int) $b['stock'] ?></span></td>
              <td class="text-end"><a href="<?= BASE ?>/admin/inventario.php?producto=<?= (int) $b['id'] ?>">Resurtir</a></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <div class="panel-caja">
      <h2 class="h5">Ventas de los últimos 14 días</h2>
      <?php if (!$ventas_dias): ?><p class="text-muted mb-0">No hubo ventas en estos días.</p>
      <?php else: foreach ($ventas_dias as $d): ?>
        <div class="barra-fila"><span><?= e(date('d/m', strtotime($d['dia']))) ?></span>
          <div class="barra"><div style="width:<?= max(3, round(100 * (float) $d['vendido'] / (float) $max_dia)) ?>%"></div></div>
          <strong><?= dinero($d['vendido']) ?></strong></div>
      <?php endforeach; endif; ?>
    </div>
    <div class="panel-caja">
      <h2 class="h5">Ventas por categoría</h2>
      <?php if (!$por_categoria): ?><p class="text-muted mb-0">Aún no hay ventas.</p>
      <?php else: foreach ($por_categoria as $c): ?>
        <div class="barra-fila"><span><?= e($c['nombre']) ?></span>
          <div class="barra"><div style="width:<?= max(3, round(100 * (float) $c['importe'] / (float) $max_cat)) ?>%"></div></div>
          <strong><?= dinero($c['importe']) ?></strong></div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel-caja">
      <h2 class="h5">Últimos pedidos</h2>
      <?php if (!$ultimos): ?><p class="text-muted mb-0">Todavía no hay pedidos.</p>
      <?php else: ?>
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($ultimos as $p): ?>
          <tr><td><a href="<?= BASE ?>/admin/pedido.php?id=<?= (int) $p['id'] ?>"><?= (int) $p['id'] ?></a></td>
              <td><?= e($p['nombre']) ?></td><td><?= dinero($p['total']) ?></td><td><?= etiqueta_estado($p['estado']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <div class="panel-caja">
      <h2 class="h5">Más vendidos</h2>
      <?php if (!$top): ?><p class="text-muted mb-0">Aún no hay ventas.</p>
      <?php else: ?>
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>Producto</th><th class="text-end">Piezas</th><th class="text-end">Importe</th></tr></thead>
        <tbody>
        <?php foreach ($top as $t): ?>
          <tr><td><?= e($t['nombre']) ?></td><td class="text-end"><?= (int) $t['piezas'] ?></td><td class="text-end"><?= dinero($t['importe']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
