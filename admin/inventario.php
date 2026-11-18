<?php
require __DIR__ . '/../inc/funciones.php';
$u = Sesion::requiereRol(['administradora']);
$titulo = 'Inventario';
$seccion = 'inventario';

if (es_post()) {
    validar_post('admin/inventario.php');
    $pid = (int) ($_POST['producto_id'] ?? 0);
    $cant = $_POST['cantidad'] ?? '';
    $p = fila('SELECT id, nombre FROM productos WHERE id = ?', [$pid]);
    if (!$p) {
        mensaje('danger', 'Elige un producto.');
    } elseif (!ctype_digit((string) $cant) || (int) $cant < 1 || (int) $cant > 9999) {
        mensaje('danger', 'La cantidad debe ser un número entre 1 y 9999.');
    } else {
        $ref = trim($_POST['referencia'] ?? '');
        transaccion(function () use ($pid, $cant, $ref, $u) {
            Inventario::mover($pid, (int) $cant, 'entrada', $ref !== '' ? mb_substr($ref, 0, 40) : 'resurtido', (int) $u['id']);
        });
        mensaje('success', 'Sumamos ' . (int) $cant . ' pieza(s) a «' . $p['nombre'] . '».');
    }
    redirigir('admin/inventario.php?producto=' . $pid);
}

$sel = (int) ($_GET['producto'] ?? 0);
$productos = filas('SELECT id, nombre, stock FROM productos ORDER BY nombre');
$donde = $sel ? ' WHERE m.producto_id = ?' : '';
$movs = filas('SELECT m.id, m.tipo, m.cantidad, m.stock_resultante, m.referencia, m.creado_en, p.nombre AS producto, u.nombre AS usuario
    FROM movimientos_stock m JOIN productos p ON p.id = m.producto_id LEFT JOIN usuarios u ON u.id = m.usuario_id'
    . $donde . ' ORDER BY m.id DESC LIMIT 80', $sel ? [$sel] : []);

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-4">Inventario</h1>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel-caja">
      <h2 class="h5">Registrar entrada de mercancía</h2>
      <form method="post" novalidate>
        <?= csrf_campo() ?>
        <div class="mb-3">
          <label for="producto_id" class="form-label">Producto</label>
          <select id="producto_id" name="producto_id" class="form-select" required>
            <option value="">Elige…</option>
            <?php foreach ($productos as $p): ?>
              <option value="<?= (int) $p['id'] ?>" <?= $sel === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nombre']) ?> (<?= (int) $p['stock'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="cantidad" class="form-label">Piezas que llegaron</label>
          <input id="cantidad" name="cantidad" type="number" min="1" max="9999" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="referencia" class="form-label">Referencia <span class="text-muted">(opcional)</span></label>
          <input id="referencia" name="referencia" class="form-control" maxlength="40" placeholder="Ej. proveedor o factura">
        </div>
        <button class="btn btn-byl w-100">Sumar al inventario</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="panel-caja p-0">
      <div class="p-3 pb-0 d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0">Últimos movimientos</h2>
        <?php if ($sel): ?><a href="<?= BASE ?>/admin/inventario.php">Ver todos</a><?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table tabla-byl align-middle mb-0 mt-2">
          <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="text-end">Cant.</th><th class="text-end">Quedó</th><th>Referencia</th><th>Quién</th></tr></thead>
          <tbody>
          <?php foreach ($movs as $m): ?>
            <tr>
              <td class="small"><?= e(fecha_corta($m['creado_en'])) ?></td>
              <td><?= e($m['producto']) ?></td>
              <td><span class="badge text-bg-<?= ['venta' => 'primary', 'cancelacion' => 'secondary', 'entrada' => 'success', 'ajuste' => 'warning', 'inicial' => 'light border'][$m['tipo']] ?? 'light' ?>"><?= e($m['tipo']) ?></span></td>
              <td class="text-end"><?= (int) $m['cantidad'] > 0 ? '+' : '' ?><?= (int) $m['cantidad'] ?></td>
              <td class="text-end"><?= (int) $m['stock_resultante'] ?></td>
              <td class="small text-muted"><?= e($m['referencia'] ?? '') ?></td>
              <td class="small"><?= e($m['usuario'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$movs): ?><tr><td colspan="7" class="text-center text-muted py-4">Sin movimientos.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
