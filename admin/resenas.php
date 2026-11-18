<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora']);
$titulo = 'Reseñas';
$seccion = 'resenas';

if (es_post()) {
    validar_post('admin/resenas.php');
    $id = (int) ($_POST['id'] ?? 0);
    $r = fila('SELECT id, visible FROM resenas WHERE id = ?', [$id]);
    if ($r && ($_POST['accion'] ?? '') === 'alternar') {
        ejecuta('UPDATE resenas SET visible = ? WHERE id = ?', [$r['visible'] ? 0 : 1, $id]);
        mensaje('info', 'La reseña ahora está ' . ($r['visible'] ? 'oculta.' : 'visible.'));
    } elseif ($r && ($_POST['accion'] ?? '') === 'borrar') {
        ejecuta('DELETE FROM resenas WHERE id = ?', [$id]);
        mensaje('success', 'Borramos la reseña.');
    }
    redirigir('admin/resenas.php');
}

$lista = filas('SELECT r.id, r.calificacion, r.comentario, r.visible, r.creado_en, p.id AS producto_id, p.nombre AS producto, u.nombre AS cliente
    FROM resenas r JOIN productos p ON p.id = r.producto_id JOIN usuarios u ON u.id = r.usuario_id ORDER BY r.id DESC LIMIT 100');
$resumen = fila('SELECT COUNT(*) AS n, AVG(calificacion) AS promedio FROM resenas WHERE visible = 1');

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-3">Reseñas de clientes</h1>
<p class="text-muted"><?= (int) $resumen['n'] ?> reseñas visibles · promedio general <?= $resumen['promedio'] !== null ? number_format((float) $resumen['promedio'], 1) : '—' ?> de 5</p>
<div class="table-responsive panel-caja p-0">
  <table class="table tabla-byl align-middle mb-0">
    <thead><tr><th>Fecha</th><th>Producto</th><th>Cliente</th><th>Calificación</th><th>Comentario</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($lista as $r): ?>
      <tr class="<?= $r['visible'] ? '' : 'fila-oculta' ?>">
        <td class="small"><?= e(fecha_corta($r['creado_en'])) ?></td>
        <td><a href="<?= BASE ?>/producto.php?id=<?= (int) $r['producto_id'] ?>#resenas"><?= e($r['producto']) ?></a></td>
        <td><?= e($r['cliente']) ?></td>
        <td><?= estrellas($r['calificacion'], 1) ?></td>
        <td class="small"><?= e($r['comentario'] ?? '') ?></td>
        <td class="text-end acciones-fila">
          <form method="post" class="d-inline"><?= csrf_campo() ?><input type="hidden" name="accion" value="alternar"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn-outline-secondary btn-sm" title="<?= $r['visible'] ? 'Ocultar' : 'Mostrar' ?>"><?= icono($r['visible'] ? 'eye-off' : 'eye', 15) ?></button></form>
          <?php boton_confirmar(icono('trash', 15), '¿Borrar esta reseña?', ['accion' => 'borrar', 'id' => $r['id']], 'btn btn-outline-danger btn-sm', 'Sí, borrar'); ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="6" class="text-center text-muted py-4">Aún no hay reseñas.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
