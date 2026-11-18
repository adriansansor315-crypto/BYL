<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['sistemas']);
$titulo = 'Respaldo y recuperación';
$seccion = 'respaldo';

if (es_post()) {
    validar_post('admin/respaldo.php');
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'descargar') {
        $nombre = 'BYL_respaldo_' . date('Ymd_His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        echo Respaldo::generar();
        exit;
    }
    if ($accion === 'restaurar') {
        $f = $_FILES['archivo'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 20 * 1024 * 1024) {
            mensaje('danger', 'No se pudo leer el archivo (máximo 20 MB).');
        } else {
            [$ok, $texto] = Respaldo::restaurar((string) file_get_contents($f['tmp_name']));
            mensaje($ok ? 'success' : 'danger', $ok ? 'Restauramos el respaldo. ' . $texto : $texto);
        }
    }
    redirigir('admin/respaldo.php');
}

$totales = [];
foreach (Respaldo::TABLAS as $t) {
    $totales[$t] = (int) fila("SELECT COUNT(*) AS n FROM `$t`")['n'];
}

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-4">Respaldo y recuperación</h1>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="panel-caja">
      <h2 class="h5">1. Descargar un respaldo</h2>
      <p>Genera un archivo .sql con todos los datos de la tienda: productos, clientes, pedidos, cupones, reseñas e inventario. Guárdalo en una USB o en tu nube.</p>
      <form method="post">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="descargar">
        <button class="btn btn-byl"><?= icono('box', 18) ?> Descargar respaldo</button>
      </form>
    </div>
    <div class="panel-caja">
      <h2 class="h5">2. Recuperar desde un respaldo</h2>
      <p class="text-muted small">Reemplaza todos los datos actuales por los del archivo. Las fotos no van en el respaldo: se conservan en la carpeta imgs/productos.</p>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="restaurar">
        <div class="mb-3">
          <label for="archivo" class="form-label">Archivo de respaldo (.sql)</label>
          <input id="archivo" name="archivo" type="file" class="form-control" accept=".sql" required>
        </div>
        <button class="btn btn-outline-byl">Recuperar datos</button>
      </form>
    </div>
    <div class="panel-caja">
      <h2 class="h5">Plan de respaldo</h2>
      <ul class="mb-0">
        <li>Descarga un respaldo al terminar cada semana de ventas y antes de hacer cambios grandes de precios o inventario.</li>
        <li>Guarda siempre dos copias en lugares distintos y conserva las últimas cuatro semanas.</li>
        <li>Cada cierto tiempo prueba la recuperación en una copia de la tienda para confirmar que el archivo sirve.</li>
        <li>Para un respaldo completo con estructura, usa «Exportar» de la base BYL en phpMyAdmin.</li>
      </ul>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel-caja">
      <h2 class="h5">Qué contiene el respaldo</h2>
      <table class="table tabla-byl mb-0">
        <thead><tr><th>Tabla</th><th class="text-end">Registros</th></tr></thead>
        <tbody>
        <?php foreach ($totales as $t => $n): ?>
          <tr><td><?= e($t) ?></td><td class="text-end"><?= $n ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
