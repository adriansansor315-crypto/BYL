<?php
require __DIR__ . '/../inc/funciones.php';
$u = Sesion::requiereRol(['administradora']);
$seccion = 'pedidos';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$ped = fila('SELECT p.*, u.nombre AS cliente, u.correo, c.codigo AS cupon
    FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id LEFT JOIN cupones c ON c.id = p.cupon_id WHERE p.id = ?', [$id]);
if (!$ped) {
    mensaje('danger', 'Ese pedido no existe.');
    redirigir('admin/pedidos.php');
}

if (es_post()) {
    validar_post('admin/pedido.php?id=' . $id);
    $nuevo = $_POST['estado'] ?? '';
    $permitido = ['pendiente' => ['confirmado', 'entregado', 'cancelado'], 'confirmado' => ['entregado', 'cancelado']];
    if (!in_array($nuevo, $permitido[$ped['estado']] ?? [], true)) {
        mensaje('danger', 'Ese cambio de estado no es válido.');
    } elseif ($nuevo === 'cancelado') {
        Pedido::cancelar($id, (int) $u['id']);
        mensaje('info', 'Cancelamos el pedido #' . $id . ' y regresamos las piezas al inventario.');
    } else {
        ejecuta('UPDATE pedidos SET estado = ? WHERE id = ?', [$nuevo, $id]);
        mensaje('success', 'El pedido #' . $id . ' ahora está ' . $nuevo . '.');
    }
    redirigir('admin/pedido.php?id=' . $id);
}

$detalle = filas('SELECT d.cantidad, d.precio_unitario, p.nombre, p.imagen
    FROM pedido_detalle d JOIN productos p ON p.id = d.producto_id WHERE d.pedido_id = ? ORDER BY p.nombre', [$id]);

$suma_detalle = (float) fila('SELECT fn_total_pedido(?) AS t', [$id])['t'];

$titulo = 'Pedido #' . $id;
require __DIR__ . '/../inc/admin_cabecera.php';
?>
<a href="<?= BASE ?>/admin/pedidos.php">← Pedidos</a>
<h1 class="h3 my-3">Pedido #<?= $id ?> <?= etiqueta_estado($ped['estado']) ?></h1>
<div class="row g-4">
  <div class="col-lg-7">
    <?php if (abs($suma_detalle - (float) $ped['subtotal']) > 0.01): ?><div class="alert alert-warning">El subtotal guardado no coincide con la suma del detalle (<?= dinero($suma_detalle) ?>).</div><?php endif; ?>
    <div class="panel-caja p-0">
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th scope="col"><span class="visually-hidden">Foto</span></th><th scope="col">Producto</th><th scope="col" class="text-end">Cant.</th><th scope="col" class="text-end">Precio</th><th scope="col" class="text-end">Importe</th></tr></thead>
        <tbody>
        <?php foreach ($detalle as $d): ?>
          <tr><td class="td-mini"><?= imagen_producto($d, 'mini') ?></td><td><?= e($d['nombre']) ?></td>
              <td class="text-end"><?= (int) $d['cantidad'] ?></td><td class="text-end"><?= dinero($d['precio_unitario']) ?></td>
              <td class="text-end"><?= dinero((float) $d['precio_unitario'] * (int) $d['cantidad']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <?php if ((float) $ped['descuento'] > 0): ?>
            <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end"><?= dinero($ped['subtotal']) ?></td></tr>
            <tr><td colspan="4" class="text-end">Cupón <?= e($ped['cupon'] ?? '') ?></td><td class="text-end">-<?= dinero($ped['descuento']) ?></td></tr>
          <?php endif; ?>
          <tr><th scope="row" colspan="4" class="text-end">Total</th><th scope="row" class="text-end"><?= dinero($ped['total']) ?></th></tr>
        </tfoot>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel-caja">
      <h2 class="h5">Cliente</h2>
      <div class="resumen-fila"><span>Nombre</span><span><?= e($ped['cliente']) ?></span></div>
      <div class="resumen-fila"><span>Correo</span><span><?= e($ped['correo']) ?></span></div>
      <div class="resumen-fila"><span>Teléfono</span><span><?= e($ped['telefono']) ?></span></div>
      <div class="resumen-fila"><span>Fecha</span><span><?= e(fecha_corta($ped['creado_en'])) ?></span></div>
      <div class="resumen-fila"><span>Entrega</span><span><?= $ped['entrega'] === 'tienda' ? 'Recoger en tienda' : 'Envío a domicilio' ?></span></div>
      <?php if ($ped['direccion_envio']): ?><div class="resumen-fila"><span>Dirección</span><span class="text-end"><?= e($ped['direccion_envio']) ?></span></div><?php endif; ?>
      <div class="resumen-fila"><span>Pago</span><span><?= e(ucfirst($ped['pago'])) ?></span></div>
      <?php if ($ped['notas']): ?><div class="resumen-fila"><span>Notas</span><span class="text-end"><?= e($ped['notas']) ?></span></div><?php endif; ?>
    </div>

    <?php if (!in_array($ped['estado'], ['entregado', 'cancelado'], true)): ?>
    <div class="panel-caja">
      <h2 class="h5">Cambiar estado</h2>
      <form method="post" class="d-flex flex-wrap gap-2">
        <?= csrf_campo() ?><input type="hidden" name="id" value="<?= $id ?>">
        <?php if ($ped['estado'] === 'pendiente'): ?><button name="estado" value="confirmado" class="btn btn-byl">Confirmar</button><?php endif; ?>
        <button name="estado" value="entregado" class="btn btn-outline-byl">Marcar entregado</button>
      </form>
      <div class="mt-3"><?php boton_confirmar('Cancelar pedido', '¿Cancelar? Las piezas regresan al inventario.', ['id' => $id, 'estado' => 'cancelado'], 'btn btn-link text-danger p-0', 'Sí, cancelar'); ?></div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
