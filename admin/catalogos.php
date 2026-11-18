<?php
require __DIR__ . '/../inc/funciones.php';
Sesion::requiereRol(['administradora']);
$titulo = 'Categorías, marcas y características';
$seccion = 'catalogos';

$tipos = [
    'categoria' => ['categorias', 'Categorías', "SELECT COUNT(*) AS n FROM productos WHERE categoria_id = ?", true],
    'marca' => ['marcas', 'Marcas', "SELECT COUNT(*) AS n FROM productos WHERE marca_id = ?", false],
    'etiqueta' => ['etiquetas', 'Características', "SELECT COUNT(*) AS n FROM producto_etiqueta WHERE etiqueta_id = ?", false],
];

if (es_post()) {
    validar_post('admin/catalogos.php');
    $tipo = $_POST['tipo'] ?? '';
    if (!isset($tipos[$tipo])) {
        redirigir('admin/catalogos.php');
    }
    [$tabla, , $sql_uso, $bloquea] = $tipos[$tipo];
    if (($_POST['accion'] ?? '') === 'agregar') {
        $nombre = trim(preg_replace('/\s+/', ' ', $_POST['nombre'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 40) {
            mensaje('danger', 'Escribe un nombre de hasta 40 letras.');
        } elseif (fila("SELECT id FROM $tabla WHERE LOWER(nombre) = ?", [mb_strtolower($nombre)])) {
            mensaje('warning', '«' . $nombre . '» ya existe.');
        } else {
            ejecuta("INSERT INTO $tabla (nombre) VALUES (?)", [$nombre]);
            mensaje('success', 'Agregamos «' . $nombre . '».');
        }
    } elseif (($_POST['accion'] ?? '') === 'borrar') {
        $id = (int) ($_POST['id'] ?? 0);
        $usados = (int) fila($sql_uso, [$id])['n'];
        if ($usados > 0 && $bloquea) {
            mensaje('warning', 'No se puede borrar: tiene ' . $usados . ' producto(s). Muévelos a otra categoría primero.');
        } else {
            ejecuta("DELETE FROM $tabla WHERE id = ?", [$id]);
            mensaje('success', 'Borramos el registro.');
        }
    }
    redirigir('admin/catalogos.php');
}

$datos = [];
foreach ($tipos as $clave => [$tabla, $nombre_bloque, , ]) {
    $col = $clave === 'etiqueta' ? 'SELECT t.id, t.nombre, COUNT(pe.producto_id) AS n FROM etiquetas t LEFT JOIN producto_etiqueta pe ON pe.etiqueta_id = t.id GROUP BY t.id, t.nombre ORDER BY t.nombre'
        : ($clave === 'categoria' ? 'SELECT c.id, c.nombre, COUNT(p.id) AS n FROM categorias c LEFT JOIN productos p ON p.categoria_id = c.id GROUP BY c.id, c.nombre ORDER BY c.nombre'
        : 'SELECT m.id, m.nombre, COUNT(p.id) AS n FROM marcas m LEFT JOIN productos p ON p.marca_id = m.id GROUP BY m.id, m.nombre ORDER BY m.nombre');
    $datos[$clave] = filas($col);
}

function bloque_catalogo(string $tipo, string $titulo_bloque, array $filas): void { ?>
  <div class="panel-caja">
    <h2 class="h5"><?= e($titulo_bloque) ?></h2>
    <form method="post" class="d-flex gap-2 mb-3">
      <?= csrf_campo() ?>
      <input type="hidden" name="tipo" value="<?= $tipo ?>"><input type="hidden" name="accion" value="agregar">
      <input name="nombre" class="form-control form-control-sm" maxlength="40" placeholder="Nombre nuevo" required aria-label="Nombre nuevo">
      <button class="btn btn-byl btn-sm"><?= icono('plus', 16) ?> Agregar</button>
    </form>
    <table class="table tabla-byl align-middle mb-0">
      <thead class="visually-hidden"><tr><th scope="col">Nombre</th><th scope="col">Productos</th><th scope="col">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($filas as $f): ?>
        <tr>
          <td><?= e($f['nombre']) ?></td>
          <td class="text-muted small"><?= (int) $f['n'] ?> producto<?= (int) $f['n'] === 1 ? '' : 's' ?></td>
          <td class="text-end"><?php boton_confirmar(icono('trash', 14), '¿Borrar «' . $f['nombre'] . '»?', ['tipo' => $tipo, 'accion' => 'borrar', 'id' => $f['id']], 'btn btn-outline-danger btn-sm', 'Sí, borrar'); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php }

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-4">Categorías, marcas y características</h1>
<div class="row g-4">
  <div class="col-lg-4"><?php bloque_catalogo('categoria', 'Categorías', $datos['categoria']); ?></div>
  <div class="col-lg-4"><?php bloque_catalogo('marca', 'Marcas', $datos['marca']); ?></div>
  <div class="col-lg-4"><?php bloque_catalogo('etiqueta', 'Características', $datos['etiqueta']); ?></div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
