<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora']);
$titulo = 'Cupones';
$seccion = 'cupones';

if (es_post()) {
    validar_post('admin/cupones.php');
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $desc = trim($_POST['descripcion'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $valor = limpiar_dinero($_POST['valor'] ?? '');
        $minimo = limpiar_dinero($_POST['minimo_compra'] ?? '');
        $usos = trim($_POST['usos_max'] ?? '');
        $hasta = trim($_POST['vigencia_hasta'] ?? '');
        $errores = [];
        if (!preg_match('/^[A-Z0-9]{3,20}$/', $codigo)) $errores[] = 'El código debe tener de 3 a 20 letras o números, sin espacios.';
        if ($codigo !== '' && fila('SELECT id FROM cupones WHERE codigo = ?', [$codigo])) $errores[] = 'Ese código ya existe.';
        if ($desc === '' || mb_strlen($desc) > 120) $errores[] = 'Escribe una descripción corta.';
        if (!in_array($tipo, ['porcentaje', 'monto'], true)) $errores[] = 'Elige el tipo de descuento.';
        if (!is_numeric($valor) || (float) $valor <= 0 || ($tipo === 'porcentaje' && (float) $valor > 100)) $errores[] = 'El valor del descuento no es válido.';
        if ($minimo !== '' && (!is_numeric($minimo) || (float) $minimo < 0)) $errores[] = 'La compra mínima no es válida.';
        if ($usos !== '' && (!ctype_digit($usos) || (int) $usos < 1)) $errores[] = 'Los usos máximos deben ser un número entero.';
        if ($hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $errores[] = 'La fecha no es válida.';
        if ($errores) {
            foreach ($errores as $err) mensaje('danger', $err);
        } else {
            ejecuta('INSERT INTO cupones (codigo, descripcion, tipo, valor, minimo_compra, usos_max, vigencia_hasta) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$codigo, $desc, $tipo, round((float) $valor, 2), $minimo === '' ? 0.0 : round((float) $minimo, 2),
                 $usos === '' ? null : (int) $usos, $hasta === '' ? null : $hasta]);
            mensaje('success', 'Creamos el cupón ' . $codigo . '.');
        }
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $c = fila('SELECT id, codigo, activo, usos FROM cupones WHERE id = ?', [$id]);
        if ($c && $accion === 'alternar') {
            ejecuta('UPDATE cupones SET activo = ? WHERE id = ?', [$c['activo'] ? 0 : 1, $id]);
            mensaje('info', 'El cupón ' . $c['codigo'] . ' ahora está ' . ($c['activo'] ? 'inactivo.' : 'activo.'));
        } elseif ($c && $accion === 'borrar') {
            ejecuta('DELETE FROM cupones WHERE id = ?', [$id]);
            mensaje('success', 'Borramos el cupón ' . $c['codigo'] . '. Los pedidos que lo usaron se conservan.');
        }
    }
    redirigir('admin/cupones.php');
}

$cupones = filas('SELECT * FROM cupones ORDER BY activo DESC, id DESC');
require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-4">Cupones de descuento</h1>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel-caja">
      <h2 class="h5">Nuevo cupón</h2>
      <form method="post" novalidate>
        <?= csrf_campo() ?><input type="hidden" name="accion" value="crear">
        <div class="mb-3"><label for="codigo" class="form-label">Código</label>
          <input id="codigo" name="codigo" class="form-control text-uppercase" maxlength="20" required></div>
        <div class="mb-3"><label for="descripcion" class="form-label">Descripción</label>
          <input id="descripcion" name="descripcion" class="form-control" maxlength="120" required></div>
        <div class="row g-2 mb-3">
          <div class="col-6"><label for="tipo" class="form-label">Tipo</label>
            <select id="tipo" name="tipo" class="form-select"><option value="porcentaje">Porcentaje (%)</option><option value="monto">Monto ($)</option></select></div>
          <div class="col-6"><label for="valor" class="form-label">Valor</label>
            <input id="valor" name="valor" class="form-control" inputmode="decimal" required></div>
        </div>
        <div class="mb-3"><label for="minimo_compra" class="form-label">Compra mínima <span class="text-muted">(opcional)</span></label>
          <input id="minimo_compra" name="minimo_compra" class="form-control" inputmode="decimal"></div>
        <div class="row g-2 mb-3">
          <div class="col-6"><label for="usos_max" class="form-label">Usos máx.</label>
            <input id="usos_max" name="usos_max" type="number" min="1" class="form-control" placeholder="Sin límite"></div>
          <div class="col-6"><label for="vigencia_hasta" class="form-label">Vence</label>
            <input id="vigencia_hasta" name="vigencia_hasta" type="date" class="form-control"></div>
        </div>
        <button class="btn btn-byl w-100">Crear cupón</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="table-responsive panel-caja p-0">
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>Código</th><th>Descuento</th><th>Mínimo</th><th>Usos</th><th>Vence</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cupones as $c): $vencido = $c['vigencia_hasta'] !== null && $c['vigencia_hasta'] < date('Y-m-d'); ?>
          <tr class="<?= $c['activo'] && !$vencido ? '' : 'fila-oculta' ?>">
            <td><strong><?= e($c['codigo']) ?></strong><br><small class="text-muted"><?= e($c['descripcion']) ?></small></td>
            <td><?= $c['tipo'] === 'porcentaje' ? rtrim(rtrim(number_format((float) $c['valor'], 2), '0'), '.') . '%' : dinero($c['valor']) ?></td>
            <td><?= (float) $c['minimo_compra'] > 0 ? dinero($c['minimo_compra']) : '—' ?></td>
            <td><?= (int) $c['usos'] ?><?= $c['usos_max'] !== null ? ' / ' . (int) $c['usos_max'] : '' ?></td>
            <td><?= e($c['vigencia_hasta'] ?? '—') ?></td>
            <td><?= !$c['activo'] ? '<span class="badge text-bg-secondary">Inactivo</span>' : ($vencido ? '<span class="badge text-bg-warning">Vencido</span>' : '<span class="badge text-bg-success">Activo</span>') ?></td>
            <td class="text-end acciones-fila">
              <form method="post" class="d-inline"><?= csrf_campo() ?><input type="hidden" name="accion" value="alternar"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-outline-secondary btn-sm" title="<?= $c['activo'] ? 'Desactivar' : 'Activar' ?>"><?= icono($c['activo'] ? 'eye-off' : 'eye', 15) ?></button></form>
              <?php boton_confirmar(icono('trash', 15), '¿Borrar el cupón ' . $c['codigo'] . '?', ['accion' => 'borrar', 'id' => $c['id']], 'btn btn-outline-danger btn-sm', 'Sí, borrar'); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
