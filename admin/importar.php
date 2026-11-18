<?php
require __DIR__ . '/../inc/funciones.php';
$u = Sesion::requiereRol(['administradora']);
$titulo = 'Importar productos';
$seccion = 'importar';
const COLUMNAS_CSV = ['nombre', 'descripcion', 'categoria', 'marca', 'contenido', 'precio', 'stock'];
const MAX_FILAS_CSV = 500;

if (($_GET['plantilla'] ?? '') === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plantilla_productos.csv"');
    echo "\xEF\xBB\xBF" . implode(',', COLUMNAS_CSV) . "\r\n" . '"Labial mate rosa","Color intenso de larga duración",Maquillaje,BISSÚ,3 g,129.00,12' . "\r\n";
    exit;
}

function id_catalogo(string $tabla, string $nombre): int
{
    $hallado = fila("SELECT id FROM $tabla WHERE nombre = ?", [$nombre]);
    if ($hallado) {
        return (int) $hallado['id'];
    }
    ejecuta("INSERT INTO $tabla (nombre) VALUES (?)", [$nombre]);
    return ultimo_id();
}

$resultado = null;
if (es_post()) {
    validar_post('admin/importar.php');
    $f = $_FILES['csv'] ?? null;
    $errores = [];
    $importados = 0;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 1024 * 1024) {
        $errores[] = 'No se pudo leer el archivo (máximo 1 MB).';
    } elseif (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $errores[] = 'El archivo debe ser .csv.';
    } else {
        $texto = file_get_contents($f['tmp_name']);
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
        $primera = strtok($texto, "\n");
        $sep = substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',';
        $flujo = fopen('php://temp', 'r+');
        fwrite($flujo, $texto);
        rewind($flujo);
        $cabecera = array_map(fn($c) => mb_strtolower(trim((string) $c)), fgetcsv($flujo, 0, $sep) ?: []);
        $faltan = array_diff(COLUMNAS_CSV, $cabecera);
        if ($faltan) {
            $errores[] = 'Faltan columnas: ' . implode(', ', $faltan) . '.';
        } else {
            $linea = 1;
            while (($fila_csv = fgetcsv($flujo, 0, $sep)) !== false) {
                $linea++;
                if ($linea - 1 > MAX_FILAS_CSV) {
                    $errores[] = 'Solo se importan las primeras ' . MAX_FILAS_CSV . ' filas.';
                    break;
                }
                if (count(array_filter($fila_csv, fn($c) => trim((string) $c) !== '')) === 0) {
                    continue;
                }
                $d = array_combine($cabecera, array_pad(array_map('trim', $fila_csv), count($cabecera), ''));
                $nombre = (string) $d['nombre'];
                $categoria = (string) $d['categoria'];
                $marca = (string) $d['marca'];
                $precio = limpiar_dinero((string) $d['precio']);
                $stock = (string) $d['stock'];
                if ($nombre === '' || mb_strlen($nombre) > 100) {
                    $errores[] = "Fila $linea: el nombre es obligatorio y de máximo 100 caracteres.";
                } elseif ($categoria === '' || mb_strlen($categoria) > 40 || mb_strlen($marca) > 40) {
                    $errores[] = "Fila $linea: categoría obligatoria y nombres de máximo 40 caracteres.";
                } elseif (!is_numeric($precio) || (float) $precio <= 0 || (float) $precio > 99999) {
                    $errores[] = "Fila $linea: el precio debe ser un número mayor que cero.";
                } elseif (!ctype_digit($stock) || (int) $stock > 9999) {
                    $errores[] = "Fila $linea: las existencias deben ser un entero de 0 a 9999.";
                } elseif (mb_strlen((string) $d['contenido']) > 30 || mb_strlen((string) $d['descripcion']) > 1000) {
                    $errores[] = "Fila $linea: contenido o descripción demasiado largos.";
                } elseif (fila('SELECT id FROM productos WHERE nombre = ?', [$nombre])) {
                    $errores[] = "Fila $linea: ya existe un producto llamado «$nombre».";
                } else {
                    transaccion(function () use ($d, $nombre, $categoria, $marca, $precio, $stock, $u) {
                        $cat = id_catalogo('categorias', $categoria);
                        $mar = $marca !== '' ? id_catalogo('marcas', $marca) : null;
                        ejecuta('INSERT INTO productos (sku, nombre, descripcion, contenido, categoria_id, marca_id, precio, stock, activo)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1)',
                            ['N' . bin2hex(random_bytes(6)), $nombre, $d['descripcion'] !== '' ? $d['descripcion'] : null,
                             $d['contenido'] !== '' ? $d['contenido'] : null, $cat, $mar, (float) $precio]);
                        $nuevo = ultimo_id();
                        ejecuta('UPDATE productos SET sku = ? WHERE id = ?', [Producto::sku($nuevo), $nuevo]);
                        if ((int) $stock > 0) {
                            Inventario::mover($nuevo, (int) $stock, 'inicial', 'importación CSV', (int) $u['id']);
                        }
                    });
                    $importados++;
                }
            }
        }
        fclose($flujo);
    }
    $resultado = ['importados' => $importados, 'errores' => $errores];
}

require __DIR__ . '/../inc/admin_cabecera.php';
?>
<h1 class="h3 mb-4">Importar productos desde CSV</h1>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="panel-caja">
      <h2 class="h5">Subir archivo</h2>
      <p class="text-muted small">Columnas en este orden: <?= e(implode(', ', COLUMNAS_CSV)) ?>. Las categorías y marcas nuevas se crean solas. Las fotos se agregan después, editando cada producto.</p>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_campo() ?>
        <div class="mb-3">
          <label for="csv" class="form-label">Archivo CSV</label>
          <input id="csv" name="csv" type="file" class="form-control" accept=".csv,text/csv" required>
        </div>
        <button class="btn btn-byl">Importar</button>
        <a class="btn btn-outline-byl" href="<?= BASE ?>/admin/importar.php?plantilla=1">Descargar plantilla</a>
      </form>
    </div>
  </div>
  <div class="col-lg-6">
    <?php if ($resultado): ?>
      <div class="panel-caja">
        <h2 class="h5">Resultado</h2>
        <p class="mb-2"><strong><?= (int) $resultado['importados'] ?></strong> productos importados.</p>
        <?php if ($resultado['errores']): ?>
          <p class="mb-1 text-danger"><?= count($resultado['errores']) ?> filas con problemas:</p>
          <ul class="small mb-0">
            <?php foreach ($resultado['errores'] as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
