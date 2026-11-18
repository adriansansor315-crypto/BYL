<?php
require __DIR__ . '/../inc/funciones.php';
$u = Sesion::requiereRol(['administradora']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$seccion = $id ? 'productos' : 'alta';
$p = $id ? fila('SELECT * FROM productos WHERE id = ?', [$id]) : null;
if ($id && !$p) {
    mensaje('danger', 'Ese producto no existe.');
    redirigir('admin/productos.php');
}
$destino_form = 'admin/producto.php' . ($id ? '?id=' . $id : '');

if (es_post()) {
    validar_post($destino_form);
    $nombre = trim(preg_replace('/\s+/', ' ', $_POST['nombre'] ?? ''));
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $desc = trim($_POST['descripcion'] ?? '');
    $uso = trim($_POST['modo_uso'] ?? '');
    $ingr = trim($_POST['ingredientes'] ?? '');
    $cont = trim($_POST['contenido'] ?? '');
    $cat = (int) ($_POST['categoria_id'] ?? 0);
    $marca = (int) ($_POST['marca_id'] ?? 0);
    $precio = limpiar_dinero($_POST['precio'] ?? '');
    $oferta = limpiar_dinero($_POST['precio_oferta'] ?? '');
    $stock = $_POST['stock'] ?? '';
    $activo = !empty($_POST['activo']) ? 1 : 0;
    $etq = array_map('intval', $_POST['etiquetas'] ?? []);

    $errores = [];
    if ($nombre === '' || mb_strlen($nombre) > 100) $errores[] = 'Escribe el nombre (máximo 100 letras).';
    if ($sku !== '' && !preg_match('/^[A-Z0-9\-]{3,20}$/', $sku)) $errores[] = 'El código solo puede llevar letras, números y guiones (3 a 20).';
    if ($sku !== '' && fila('SELECT id FROM productos WHERE sku = ? AND id <> ?', [$sku, $id])) $errores[] = 'Ya existe un producto con ese código.';
    if (mb_strlen($desc) > 1000 || mb_strlen($uso) > 1000 || mb_strlen($ingr) > 1000) $errores[] = 'Alguno de los textos es muy largo (máximo 1000 letras).';
    if (mb_strlen($cont) > 30) $errores[] = 'El contenido es muy largo (ej. 30 ml).';
    if (!fila('SELECT id FROM categorias WHERE id = ?', [$cat])) $errores[] = 'Elige una categoría.';
    if ($marca && !fila('SELECT id FROM marcas WHERE id = ?', [$marca])) $errores[] = 'La marca no existe.';
    if (!is_numeric($precio) || (float) $precio <= 0) $errores[] = 'El precio debe ser mayor que 0.';
    if ($oferta !== '' && (!is_numeric($oferta) || (float) $oferta <= 0 || (float) $oferta >= (float) $precio)) {
        $errores[] = 'El precio de oferta debe ser mayor que 0 y menor que el precio normal.';
    }
    if (!ctype_digit((string) $stock)) $errores[] = 'Las piezas deben ser un número entero (0 o más).';

    $foto = null;
    if (!$errores && isset($_FILES['imagen'])) {
        $error_foto = null;
        $foto = Foto::guardar($_FILES['imagen'], $error_foto);
        if ($error_foto) $errores[] = $error_foto;
    }

    if ($errores) {
        foreach ($errores as $err) mensaje('danger', $err);
        $_SESSION['form_producto'] = ['nombre' => $nombre, 'sku' => $sku, 'descripcion' => $desc, 'modo_uso' => $uso, 'ingredientes' => $ingr,
            'contenido' => $cont, 'categoria_id' => $cat, 'marca_id' => $marca, 'precio' => $precio, 'precio_oferta' => $oferta,
            'stock' => $stock, 'activo' => $activo, 'etiquetas' => $etq];
        redirigir($destino_form);
    }

    $precio = round((float) $precio, 2);
    $oferta = $oferta === '' ? null : round((float) $oferta, 2);
    $marca_db = $marca ?: null;
    $nulo = fn($t) => $t !== '' ? $t : null;
    $etq = array_values(array_filter($etq, fn($x) => fila('SELECT id FROM etiquetas WHERE id = ?', [$x])));

    if ($id) {
        transaccion(function () use ($id, $p, $u, $nombre, $sku, $desc, $uso, $ingr, $cont, $cat, $marca_db, $precio, $oferta, $stock, $activo, $etq, $foto, $nulo) {
            ejecuta('UPDATE productos SET nombre = ?, sku = ?, descripcion = ?, modo_uso = ?, ingredientes = ?, contenido = ?, categoria_id = ?, marca_id = ?,
                precio = ?, precio_oferta = ?, activo = ? WHERE id = ?',
                [$nombre, $sku !== '' ? $sku : $p['sku'], $nulo($desc), $nulo($uso), $nulo($ingr), $nulo($cont), $cat, $marca_db, $precio, $oferta, $activo, $id]);
            $dif = (int) $stock - (int) $p['stock'];
            if ($dif !== 0) {
                Inventario::mover($id, $dif, 'ajuste', 'edición del producto', (int) $u['id']);
            }
            ejecuta('DELETE FROM producto_etiqueta WHERE producto_id = ?', [$id]);
            foreach ($etq as $e_id) {
                ejecuta('INSERT INTO producto_etiqueta (producto_id, etiqueta_id) VALUES (?, ?)', [$id, $e_id]);
            }
            if ($foto) {
                ejecuta('UPDATE productos SET imagen = ? WHERE id = ?', [$foto, $id]);
            }
        });
        if ($foto) {
            Foto::borrar($p['imagen']);
        }
        mensaje('success', 'Guardamos los cambios de «' . $nombre . '».');
    } else {
        transaccion(function () use ($u, $nombre, $sku, $desc, $uso, $ingr, $cont, $cat, $marca_db, $precio, $oferta, $stock, $activo, $etq, $foto, $nulo) {
            ejecuta('INSERT INTO productos (sku, nombre, descripcion, modo_uso, ingredientes, contenido, categoria_id, marca_id, precio, precio_oferta, stock, imagen, activo)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)',
                [$sku !== '' ? $sku : 'N' . bin2hex(random_bytes(6)), $nombre, $nulo($desc), $nulo($uso), $nulo($ingr), $nulo($cont), $cat, $marca_db, $precio, $oferta, $foto, $activo]);
            $nuevo = ultimo_id();
            if ($sku === '') {
                ejecuta('UPDATE productos SET sku = ? WHERE id = ?', [Producto::sku($nuevo), $nuevo]);
            }
            if ((int) $stock > 0) {
                Inventario::mover($nuevo, (int) $stock, 'inicial', 'alta del producto', (int) $u['id']);
            }
            foreach ($etq as $e_id) {
                ejecuta('INSERT INTO producto_etiqueta (producto_id, etiqueta_id) VALUES (?, ?)', [$nuevo, $e_id]);
            }
        });
        mensaje('success', 'Agregamos «' . $nombre . '» al catálogo.');
    }
    redirigir('admin/productos.php');
}

$guardado = $_SESSION['form_producto'] ?? null;
unset($_SESSION['form_producto']);
$marcadas = $id ? array_map('intval', array_column(filas('SELECT etiqueta_id FROM producto_etiqueta WHERE producto_id = ?', [$id]), 'etiqueta_id')) : [];
if ($guardado) {
    $marcadas = $guardado['etiquetas'];
    $p = array_merge($p ?? [], $guardado);
}
$v = fn($campo, $def = '') => $p[$campo] ?? $def;

$categorias = filas('SELECT id, nombre FROM categorias ORDER BY nombre');
$marcas = filas('SELECT id, nombre FROM marcas ORDER BY nombre');
$etiquetas = filas('SELECT id, nombre FROM etiquetas ORDER BY nombre');

$titulo = $id ? 'Editar producto' : 'Nuevo producto';
require __DIR__ . '/../inc/admin_cabecera.php';
?>
<a href="<?= BASE ?>/admin/productos.php">← Productos</a>
<h1 class="h3 my-3"><?= e($titulo) ?></h1>

<form method="post" enctype="multipart/form-data" class="row g-4" novalidate>
  <?= csrf_campo() ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <div class="col-lg-8">
    <div class="panel-caja">
      <div class="row g-3 mb-3">
        <div class="col-md-8">
          <label for="nombre" class="form-label">Nombre</label>
          <input id="nombre" name="nombre" class="form-control" maxlength="100" value="<?= e($v('nombre')) ?>" required>
        </div>
        <div class="col-md-4">
          <label for="sku" class="form-label">Código <span class="text-muted">(opcional)</span></label>
          <input id="sku" name="sku" class="form-control" maxlength="20" value="<?= e($v('sku')) ?>" placeholder="Se genera solo">
        </div>
      </div>
      <div class="mb-3">
        <label for="descripcion" class="form-label">¿Para qué sirve? <span class="text-muted">(lo ve la clienta en la ficha)</span></label>
        <textarea id="descripcion" name="descripcion" class="form-control" rows="3" maxlength="1000"><?= e($v('descripcion')) ?></textarea>
      </div>
      <div class="mb-3">
        <label for="modo_uso" class="form-label">Modo de uso</label>
        <textarea id="modo_uso" name="modo_uso" class="form-control" rows="2" maxlength="1000"><?= e($v('modo_uso')) ?></textarea>
      </div>
      <div class="mb-3">
        <label for="ingredientes" class="form-label">Ingredientes</label>
        <textarea id="ingredientes" name="ingredientes" class="form-control" rows="2" maxlength="1000"><?= e($v('ingredientes')) ?></textarea>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <label for="categoria_id" class="form-label">Categoría</label>
          <select id="categoria_id" name="categoria_id" class="form-select" required>
            <option value="">Elige…</option>
            <?php foreach ($categorias as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= (int) $v('categoria_id', 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="marca_id" class="form-label">Marca <span class="text-muted">(opcional)</span></label>
          <select id="marca_id" name="marca_id" class="form-select">
            <option value="">Sin marca</option>
            <?php foreach ($marcas as $m): ?>
              <option value="<?= (int) $m['id'] ?>" <?= (int) $v('marca_id', 0) === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="contenido" class="form-label">Contenido</label>
          <input id="contenido" name="contenido" class="form-control" maxlength="30" value="<?= e($v('contenido')) ?>" placeholder="Ej. 30 ml">
        </div>
      </div>
      <div class="mt-3">
        <div class="form-label">Características</div>
        <div class="checks">
          <?php foreach ($etiquetas as $t): ?>
            <label class="form-check"><input class="form-check-input" type="checkbox" name="etiquetas[]" value="<?= (int) $t['id'] ?>" <?= in_array((int) $t['id'], $marcadas, true) ? 'checked' : '' ?>>
              <span class="form-check-label"><?= e($t['nombre']) ?></span></label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="panel-caja">
      <div class="mb-3">
        <label for="precio" class="form-label">Precio</label>
        <div class="input-group"><span class="input-group-text">$</span>
          <input id="precio" name="precio" class="form-control" inputmode="decimal" value="<?= e($v('precio')) ?>" required></div>
      </div>
      <div class="mb-3">
        <label for="precio_oferta" class="form-label">Precio de oferta <span class="text-muted">(opcional)</span></label>
        <div class="input-group"><span class="input-group-text">$</span>
          <input id="precio_oferta" name="precio_oferta" class="form-control" inputmode="decimal" value="<?= e($v('precio_oferta')) ?>"></div>
        <div class="form-text">Déjalo vacío si no está en oferta.</div>
      </div>
      <div class="mb-3">
        <label for="stock" class="form-label">Piezas en existencia</label>
        <input id="stock" name="stock" type="number" min="0" class="form-control" value="<?= e($v('stock', 0)) ?>" required>
      </div>
      <div class="mb-3">
        <label for="imagen" class="form-label">Foto <span class="text-muted">(JPG, PNG o WEBP, máx. 2 MB)</span></label>
        <?php if (!empty($p['imagen'])): ?>
          <div class="mb-2"><?= imagen_producto($p, 'mini mini-grande') ?></div>
        <?php endif; ?>
        <input id="imagen" name="imagen" type="file" class="form-control" accept="image/jpeg,image/png,image/webp">
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= (int) $v('activo', 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="activo">Visible en la tienda</label>
      </div>
      <button class="btn btn-byl w-100">Guardar producto</button>
    </div>
  </div>
</form>
<?php require __DIR__ . '/../inc/admin_pie.php'; ?>
