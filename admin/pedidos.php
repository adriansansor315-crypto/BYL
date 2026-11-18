<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora']);
$titulo = 'Pedidos';
$seccion = 'pedidos';

$estado = $_GET['estado'] ?? '';
$estados = ['pendiente', 'confirmado', 'entregado', 'cancelado'];
$par = [];
$where = '';
if (in_array($estado, $estados, true)) {
    $where = ' WHERE p.estado = ?';
    $par[] = $estado;
}
$pedidos = filas('SELECT p.id, p.estado, p.total, p.creado_en, p.entrega, p.pago, u.nombre, p.telefono
    FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id' . $where . ' ORDER BY p.id DESC LIMIT 100', $par);
$cuentas = [];
foreach (filas('SELECT estado, COUNT(*) AS n FROM pedidos GROUP BY estado') as $c) {
    $cuentas[$c['estado']] = (int) $c['n'];
}

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-3">Pedidos</h1>
<div class="pestanas mb-3">
  <a class="<?= $estado === '' ? 'activa' : '' ?>" href="<?= BASE ?>/admin/pedidos.php">Todos (<?= array_sum($cuentas) ?>)</a>
  <?php foreach ($estados as $e_): ?>
    <a class="<?= $estado === $e_ ? 'activa' : '' ?>" href="<?= BASE ?>/admin/pedidos.php?estado=<?= $e_ ?>"><?= e(ucfirst($e_)) ?> (<?= $cuentas[$e_] ?? 0 ?>)</a>
  <?php endforeach; ?>
</div>
<div class="table-responsive panel-caja p-0">
  <table class="table tabla-byl align-middle mb-0">
    <thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Teléfono</th><th>Entrega</th><th>Pago</th><th class="text-end">Total</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pedidos as $p): ?>
      <tr>
        <td><?= (int) $p['id'] ?></td><td><?= e(fecha_corta($p['creado_en'])) ?></td><td><?= e($p['nombre']) ?></td>
        <td><?= e($p['telefono']) ?></td>
        <td><?= $p['entrega'] === 'tienda' ? 'En tienda' : 'A domicilio' ?></td><td><?= e(ucfirst($p['pago'])) ?></td>
        <td class="text-end"><?= dinero($p['total']) ?></td><td><?= etiqueta_estado($p['estado']) ?></td>
        <td class="text-end"><a class="btn btn-outline-byl btn-sm" href="<?= BASE ?>/admin/pedido.php?id=<?= (int) $p['id'] ?>">Abrir</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$pedidos): ?><tr><td colspan="9" class="text-center text-muted py-4">No hay pedidos.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
