<?php
require __DIR__ . '/../inc/funciones.php';
$u = Sesion::requiereRol(['administradora', 'sistemas']);
$titulo = 'Cuentas';
$seccion = 'cuentas';

if (es_post()) {
    validar_post('admin/cuentas.php');
    $id = (int) ($_POST['id'] ?? 0);
    $cuenta = fila('SELECT u.id, u.activo, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ?', [$id]);
    if (!$cuenta || (int) $cuenta['id'] === (int) $u['id']) {
        mensaje('danger', 'No puedes cambiar esa cuenta.');
    } elseif ($u['rol'] === 'administradora' && $cuenta['rol'] !== 'cliente') {
        mensaje('danger', 'La administradora solo gestiona cuentas de clientas.');
    } else {
        $nuevo = $cuenta['activo'] ? 0 : 1;
        ejecuta('UPDATE usuarios SET activo = ? WHERE id = ?', [$nuevo, $id]);
        mensaje('success', $nuevo ? 'Reactivamos la cuenta.' : 'Desactivamos la cuenta. Ya no puede iniciar sesión.');
    }
    redirigir('admin/cuentas.php');
}

$q = trim($_GET['q'] ?? '');
$donde = '';
$params = [];
if ($q !== '') {
    $donde = ' WHERE u.nombre LIKE ? OR u.correo LIKE ? OR u.usuario LIKE ?';
    $params = array_fill(0, 3, '%' . $q . '%');
}
$lista = filas('SELECT u.id, u.nombre, u.correo, u.usuario, u.activo, u.creado_en, r.nombre AS rol,
        (SELECT COUNT(*) FROM pedidos p WHERE p.usuario_id = u.id) AS pedidos
    FROM usuarios u JOIN roles r ON r.id = u.rol_id' . $donde . ' ORDER BY r.id, u.id LIMIT 200', $params);

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 mb-0">Cuentas <span class="text-muted fs-6">(<?= count($lista) ?>)</span></h1>
</div>
<form class="barra-filtros" method="get">
  <label for="q" class="visually-hidden">Buscar cuenta</label>
  <input id="q" type="search" name="q" value="<?= e($q) ?>" class="form-control form-control-sm" placeholder="Buscar por nombre, correo o usuario">
  <button class="btn btn-outline-byl btn-sm">Buscar</button>
</form>
<div class="table-responsive panel-caja p-0">
  <table class="table tabla-byl align-middle mb-0">
    <thead><tr><th scope="col">Nombre</th><th scope="col">Correo</th><th scope="col">Perfil</th><th scope="col" class="text-end">Pedidos</th><th scope="col">Alta</th><th scope="col">Estado</th><th scope="col" class="text-end">Acción</th></tr></thead>
    <tbody>
    <?php foreach ($lista as $c): ?>
      <tr>
        <td><?= e($c['nombre']) ?></td>
        <td class="small"><?= e($c['correo']) ?></td>
        <td><?= e(ucfirst($c['rol'])) ?></td>
        <td class="text-end"><?= (int) $c['pedidos'] ?></td>
        <td class="small"><?= e(fecha_corta($c['creado_en'])) ?></td>
        <td><?= $c['activo'] ? '<span class="badge text-bg-success">Activa</span>' : '<span class="badge text-bg-secondary">Desactivada</span>' ?></td>
        <td class="text-end acciones-fila">
          <?php if ((int) $c['id'] === (int) $u['id'] || ($u['rol'] === 'administradora' && $c['rol'] !== 'cliente')): ?>
            <span class="text-muted small">—</span>
          <?php elseif ($c['activo']): ?>
            <?php boton_confirmar('Desactivar', '¿Desactivar la cuenta de ' . $c['nombre'] . '?', ['id' => $c['id']], 'btn btn-outline-danger btn-sm', 'Sí, desactivar'); ?>
          <?php else: ?>
            <form method="post" class="d-inline"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-outline-secondary btn-sm">Reactivar</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="7" class="text-center text-muted py-4">No encontramos cuentas.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
