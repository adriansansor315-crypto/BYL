<?php
$ciudades = ['Tizimín', 'Valladolid', 'Espita', 'Panabá', 'Río Lagartos', 'Mérida', 'Otra'];
$generos = ['mujer' => 'Mujer', 'hombre' => 'Hombre', 'sin_decir' => 'Prefiero no decirlo'];
$intereses_cat = filas('SELECT id, nombre FROM categorias ORDER BY nombre');
$d = $datos ?? [];
$sel_int = array_map('intval', $d['intereses'] ?? []);
$modo_alta = $modo_alta ?? false;
?>
<div class="form-encabezado">
  <span class="form-proyecto"><?= e(TIENDA) ?></span>
  <h1 class="form-pantalla"><?= $modo_alta ? 'Registro de clienta' : 'Mi perfil' ?></h1>
</div>
<form method="post" enctype="multipart/form-data" class="form-byl" id="form-perfil" action="">
  <?= csrf_campo() ?>
  <input type="hidden" name="accion" value="datos">
  <fieldset>
    <legend>Datos personales</legend>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre completo</label>
        <input id="nombre" type="text" name="nombre" class="form-control" value="<?= e($d['nombre'] ?? '') ?>" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,80}" title="Solo letras y espacios (mínimo 3)" maxlength="80" autocomplete="name" required>
      </div>
      <div class="col-md-6">
        <label for="telefono" class="form-label">Teléfono <small>(10 dígitos)</small></label>
        <input id="telefono" type="text" name="telefono" class="form-control" value="<?= e($d['telefono'] ?? '') ?>" pattern="[0-9]{10}" title="Solo números, 10 dígitos" inputmode="numeric" maxlength="10" autocomplete="tel" required>
      </div>
      <div class="col-md-6">
        <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
        <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" class="form-control" value="<?= e($d['fecha_nacimiento'] ?? '') ?>" min="1920-01-01" max="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-md-6">
        <label for="ciudad" class="form-label">Ciudad</label>
        <select id="ciudad" name="ciudad" class="form-select" required>
          <option value="">Elige tu ciudad</option>
          <?php foreach ($ciudades as $c): ?>
            <option value="<?= e($c) ?>" <?= ($d['ciudad'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <span class="form-label d-block">Género</span>
        <?php foreach ($generos as $clave => $texto): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="genero" id="genero_<?= $clave ?>" value="<?= $clave ?>" <?= ($d['genero'] ?? '') === $clave ? 'checked' : '' ?> required>
            <label class="form-check-label" for="genero_<?= $clave ?>"><?= e($texto) ?></label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="col-12">
        <label for="direccion" class="form-label">Dirección para entregas</label>
        <textarea id="direccion" name="direccion" class="form-control" rows="2" maxlength="200" placeholder="Calle, número, colonia" required><?= e($d['direccion'] ?? '') ?></textarea>
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Tus gustos</legend>
    <div class="row g-3">
      <div class="col-12">
        <span class="form-label d-block">¿Qué te interesa?</span>
        <?php foreach ($intereses_cat as $c): ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="intereses[]" id="int_<?= (int) $c['id'] ?>" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $sel_int, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="int_<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="col-md-4">
        <label for="frecuencia_compra" class="form-label">Compras de maquillaje al mes</label>
        <input id="frecuencia_compra" type="number" name="frecuencia_compra" class="form-control" value="<?= e($d['frecuencia_compra'] ?? '') ?>" min="0" max="30" step="1" required>
      </div>
      <div class="col-md-4">
        <label for="color_favorito" class="form-label">Color favorito</label>
        <input id="color_favorito" type="color" name="color_favorito" class="form-control form-control-color" value="<?= e($d['color_favorito'] ?? '#6A4BC4') ?>" required>
      </div>
      <div class="col-md-4">
        <label for="hora_contacto" class="form-label">Hora para contactarte</label>
        <input id="hora_contacto" type="time" name="hora_contacto" class="form-control" value="<?= e(substr($d['hora_contacto'] ?? '', 0, 5)) ?>" required>
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="recibir_promos" id="recibir_promos" value="1" <?= !empty($d['recibir_promos']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="recibir_promos">Quiero enterarme de promociones y novedades</label>
        </div>
      </div>
      <div class="col-12">
        <label for="foto" class="form-label">Foto de perfil <small>(opcional, JPG o PNG, máx. 2 MB)</small></label>
        <input id="foto" type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Acceso</legend>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="correo" class="form-label">Correo electrónico</label>
        <input id="correo" type="email" name="correo" class="form-control" value="<?= e($d['correo'] ?? '') ?>" pattern="[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}" title="Formato usuario@dominio.com" maxlength="120" autocomplete="email" required>
      </div>
      <?php if ($modo_alta): ?>
      <div class="col-md-6">
        <label for="clave" class="form-label">Contraseña <small>(mínimo 8 caracteres)</small></label>
        <input id="clave" type="password" name="clave" class="form-control" minlength="8" maxlength="100" autocomplete="new-password" required>
      </div>
      <?php endif; ?>
    </div>
  </fieldset>

  <div class="form-botones">
    <button type="submit" class="btn btn-byl btn-lg"><?= $modo_alta ? 'Crear cuenta' : 'Guardar cambios' ?></button>
    <button type="reset" class="btn btn-outline-byl btn-lg">Limpiar</button>
  </div>
</form>
