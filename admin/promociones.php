<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora']);
$titulo = 'Promociones';
$seccion = 'promos';

if (es_post()) {
    validar_post('admin/promociones.php');
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $texto = trim(preg_replace('/\s+/', ' ', $_POST['titulo'] ?? ''));
        $enlace = trim($_POST['enlace'] ?? '') ?: 'catalogo.php';
        $orden = (int) ($_POST['orden'] ?? 0);
        $error = null;
        if ($texto === '' || mb_strlen($texto) > 120) {
            mensaje('danger', 'Escribe un título de hasta 120 letras.');
        } elseif (!preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9=&%_\-.]*)?$/', $enlace)) {
            mensaje('danger', 'El enlace debe ser una página de la tienda, por ejemplo catalogo.php?categoria=1.');
        } else {
            $foto = Foto::guardar($_FILES['imagen'] ?? ['error' => UPLOAD_ERR_NO_FILE], $error, 'promos');
            if ($foto === null) {
                mensaje('danger', $error ?: 'Sube la imagen de la promoción.');
            } else {
                ejecuta('INSERT INTO promociones (titulo, imagen, enlace, orden) VALUES (?, ?, ?, ?)', [$texto, $foto, $enlace, $orden]);
                mensaje('success', 'Agregamos la promoción «' . $texto . '» al carrusel.');
            }
        }
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $p = fila('SELECT id, titulo, imagen, activo FROM promociones WHERE id = ?', [$id]);
        if ($p && $accion === 'alternar') {
            ejecuta('UPDATE promociones SET activo = ? WHERE id = ?', [$p['activo'] ? 0 : 1, $id]);
            mensaje('info', 'La promoción ahora está ' . ($p['activo'] ? 'oculta.' : 'visible.'));
        } elseif ($p && $accion === 'borrar') {
            ejecuta('DELETE FROM promociones WHERE id = ?', [$id]);
            if (Foto::esValida($p['imagen']) && !fila('SELECT id FROM promociones WHERE imagen = ?', [$p['imagen']])) {
                @unlink(__DIR__ . '/../imgs/promos/' . $p['imagen']);
            }
            mensaje('success', 'Quitamos la promoción «' . $p['titulo'] . '».');
        }
    }
    redirigir('admin/promociones.php');
}

$promos = filas('SELECT * FROM promociones ORDER BY orden, id');
require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-4">Promociones del carrusel</h1>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="panel-caja">
      <h2 class="h5">Nueva promoción</h2>
      <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_campo() ?><input type="hidden" name="accion" value="crear">
        <div class="mb-3"><label for="titulo" class="form-label">Título</label>
          <input id="titulo" name="titulo" class="form-control" maxlength="120" required></div>
        <div class="mb-3"><label for="enlace" class="form-label">A dónde lleva</label>
          <input id="enlace" name="enlace" class="form-control" value="catalogo.php" maxlength="160">
          <div class="form-text">Ejemplo: catalogo.php?ofertas=1</div></div>
        <div class="mb-3"><label for="orden" class="form-label">Posición</label>
          <input id="orden" name="orden" type="number" min="0" class="form-control" value="<?= count($promos) + 1 ?>"></div>
        <div class="mb-3"><label for="imagen" class="form-label">Imagen</label>
          <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="form-control" required>
          <div class="form-text">Cuadrada se ve mejor. Máximo 2 MB.</div></div>
        <button class="btn btn-byl w-100">Agregar al carrusel</button>
      </form>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="panel-caja">
      <table class="table tabla-byl align-middle mb-0">
        <thead><tr><th>Imagen</th><th>Título</th><th>Posición</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($promos as $p): ?>
          <tr class="<?= $p['activo'] ? '' : 'fila-oculta' ?>">
            <td class="td-mini"><img class="prod-img mini" src="<?= BASE ?>/imgs/promos/<?= e($p['imagen']) ?>" alt=""></td>
            <td><?= e($p['titulo']) ?><div class="small text-muted"><?= e($p['enlace']) ?></div></td>
            <td><?= (int) $p['orden'] ?></td>
            <td><?= $p['activo'] ? 'Visible' : 'Oculta' ?></td>
            <td class="text-end acciones-fila">
              <form method="post" class="d-inline"><?= csrf_campo() ?><input type="hidden" name="accion" value="alternar"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="btn btn-outline-byl btn-sm"><?= $p['activo'] ? 'Ocultar' : 'Mostrar' ?></button></form>
              <?php boton_confirmar(icono('trash', 14), '¿Quitar «' . $p['titulo'] . '» del carrusel?', ['accion' => 'borrar', 'id' => $p['id']], 'btn btn-outline-danger btn-sm', 'Sí, quitar'); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$promos): ?><tr><td colspan="5" class="text-muted text-center py-4">No hay promociones. La portada mostrará la bienvenida normal.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
