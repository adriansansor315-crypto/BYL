<?php
require_once __DIR__ . '/conexion.php';

spl_autoload_register(function (string $clase) {
    $archivo = __DIR__ . '/../clases/' . $clase . '.php';
    if (is_file($archivo)) {
        require_once $archivo;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

const SQL_PRODUCTO = 'SELECT p.*, c.nombre AS categoria, m.nombre AS marca,
        fn_precio_final(p.id) AS precio_final,
        fn_promedio_producto(p.id) AS promedio,
        (SELECT COUNT(*) FROM resenas r WHERE r.producto_id = p.id AND r.visible = 1) AS n_resenas
    FROM productos p
    JOIN categorias c ON c.id = p.categoria_id
    LEFT JOIN marcas m ON m.id = p.marca_id';

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function dinero($n): string
{
    return '$' . number_format((float) $n, 2);
}

function redirigir(string $ruta): void
{
    header('Location: ' . BASE . '/' . ltrim($ruta, '/'));
    exit;
}

function ruta_segura(?string $ruta, string $por_defecto = 'index.php'): string
{
    if ($ruta !== null && preg_match('~^(admin/)?[a-z_]+\.php(\?[A-Za-z0-9_=&%.+\-]*)?$~', $ruta)) {
        return $ruta;
    }
    return $por_defecto;
}

function ruta_actual(): string
{
    $actual = basename($_SERVER['SCRIPT_NAME']);
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
        $actual = 'admin/' . $actual;
    }
    $q = $_SERVER['QUERY_STRING'] ?? '';
    return ruta_segura($actual . ($q !== '' ? '?' . $q : ''));
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensajes'][] = [$tipo, $texto];
}

function mostrar_mensajes(): void
{
    foreach ($_SESSION['mensajes'] ?? [] as [$tipo, $texto]) {
        echo '<div class="alert alert-' . e($tipo) . '" role="alert">' . e($texto) . '</div>';
    }
    unset($_SESSION['mensajes']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function validar_post(string $volver = ''): void
{
    if (!es_post()) {
        redirigir('index.php');
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        mensaje('danger', 'La página caducó. Vuelve a intentarlo.');
        redirigir($volver !== '' ? $volver : ruta_segura($_POST['volver'] ?? null));
    }
}

function url_catalogo(array $cambios = []): string
{
    $q = array_merge($_GET, $cambios);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null || $v === 0 || $v === '0') {
            unset($q[$k]);
        }
    }
    return BASE . '/catalogo.php' . ($q ? '?' . http_build_query($q) : '');
}

function fecha_corta(string $ts): string
{
    $t = strtotime($ts);
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1] . ' ' . date('Y, H:i', $t);
}

function limpiar_dinero(string $texto): string
{
    return str_replace([',', '$', ' '], '', $texto);
}

function estrellas($promedio, int $n = 0): string
{
    if ($promedio === null || $n === 0) {
        return '<span class="estrellas sin">Sin reseñas</span>';
    }
    $llenas = (int) round((float) $promedio);
    return '<span class="estrellas" title="' . number_format((float) $promedio, 1) . ' de 5">'
        . str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas) . ' <small>(' . $n . ')</small></span>';
}

function etiqueta_estado(string $estado): string
{
    $clases = ['pendiente' => 'warning', 'confirmado' => 'info', 'entregado' => 'success', 'cancelado' => 'secondary'];
    return '<span class="badge text-bg-' . ($clases[$estado] ?? 'secondary') . '">' . e(ucfirst($estado)) . '</span>';
}

function icono(string $nombre, int $tam = 20): string
{
    static $trazos = [
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'heart'  => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'cart'   => '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'user'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'plus'   => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'trash'  => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>',
        'check'  => '<polyline points="20 6 9 17 4 12"/>',
        'edit'   => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'truck'  => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'store'  => '<path d="M3 9l1-5h16l1 5"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v8h14v-8"/>',
        'tag'    => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'star'   => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'box'    => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'home'   => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'list'   => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>',
        'eye'    => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'=> '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>',
        'coupon' => '<path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/><line x1="12" y1="6" x2="12" y2="18" stroke-dasharray="2 3"/>',
        'chat'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'stock'  => '<path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/>',
    ];
    $t = $trazos[$nombre] ?? '';
    return '<svg class="ico" width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $t . '</svg>';
}

function imagen_producto(array $p, string $clase = ''): string
{
    $archivo = $p['imagen'] ?? '';
    if ($archivo === '' || !is_file(__DIR__ . '/../imgs/productos/' . $archivo)) {
        $archivo = '';
    }
    if ($archivo === '') {
        return '<div class="prod-img prod-vacio ' . e($clase) . '" role="img" aria-label="Sin foto">' . e(mb_substr($p['nombre'], 0, 1)) . '</div>';
    }
    return '<img class="prod-img ' . e($clase) . '" src="' . BASE . '/imgs/productos/' . e($archivo) . '" alt="' . e($p['nombre']) . '" loading="lazy">';
}

function boton_confirmar(string $etiqueta, string $pregunta, array $campos, string $clase = 'btn btn-outline-danger btn-sm', string $boton = 'Si, continuar'): void
{
    echo '<details class="confirmar"><summary class="' . e($clase) . '">' . $etiqueta . '</summary>';
    echo '<form method="post" class="confirmar-caja">' . csrf_campo();
    foreach ($campos as $k => $v) {
        echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    echo '<span>' . e($pregunta) . '</span> <button class="btn btn-danger btn-sm">' . e($boton) . '</button></form></details>';
}

function campos_volver(string $volver): string
{
    return '<input type="hidden" name="volver" value="' . e($volver) . '">';
}

function tarjeta_producto(array $p, array $deseados = []): void
{
    $pct = (new Producto($p))->descuento();
    $agotado = (int) $p['stock'] <= 0;
    $en_deseos = in_array((int) $p['id'], $deseados, true);
    $volver = ruta_actual();
    ?>
    <div class="col">
      <article class="tarjeta <?= $agotado ? 'agotado' : '' ?>">
        <a class="tarjeta-img" href="<?= BASE ?>/producto.php?id=<?= (int) $p['id'] ?>">
          <?= imagen_producto($p) ?>
          <?php if ($agotado): ?><span class="etiqueta etiqueta-gris">Agotado</span>
          <?php elseif ($pct > 0): ?><span class="etiqueta">-<?= $pct ?>%</span><?php endif; ?>
        </a>
        <form class="tarjeta-deseo" method="post" action="<?= BASE ?>/deseos.php">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="alternar">
          <input type="hidden" name="producto_id" value="<?= (int) $p['id'] ?>">
          <?= campos_volver($volver) ?>
          <button class="btn-deseo <?= $en_deseos ? 'activo' : '' ?>" title="<?= $en_deseos ? 'Quitar de mis deseos' : 'Guardar en mis deseos' ?>" aria-label="Guardar en mis deseos"><?= icono('heart', 18) ?></button>
        </form>
        <div class="tarjeta-cuerpo">
          <?php if (!empty($p['marca'])): ?><div class="tarjeta-marca"><?= e($p['marca']) ?></div><?php endif; ?>
          <a class="tarjeta-nombre" href="<?= BASE ?>/producto.php?id=<?= (int) $p['id'] ?>"><?= e($p['nombre']) ?></a>
          <?php if (isset($p['n_resenas']) && (int) $p['n_resenas'] > 0): ?><div><?= estrellas($p['promedio'], (int) $p['n_resenas']) ?></div><?php endif; ?>
          <div class="precio-fila">
            <span class="precio <?= $pct > 0 ? 'precio-oferta' : '' ?>"><?= dinero($p['precio_final']) ?></span>
            <?php if ($pct > 0): ?><span class="precio-viejo"><?= dinero($p['precio']) ?></span><?php endif; ?>
          </div>
          <?php if (!$agotado && (int) $p['stock'] <= STOCK_BAJO): ?><div class="poco-stock">Quedan <?= (int) $p['stock'] ?></div><?php endif; ?>
          <?php if (!$agotado): ?>
          <form method="post" action="<?= BASE ?>/carrito.php">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="producto_id" value="<?= (int) $p['id'] ?>">
            <input type="hidden" name="cantidad" value="1">
            <?= campos_volver($volver) ?>
            <button class="btn btn-byl btn-sm w-100"><?= icono('cart', 16) ?> Agregar</button>
          </form>
          <?php else: ?>
          <button class="btn btn-outline-secondary btn-sm w-100" disabled>Agotado</button>
          <?php endif; ?>
        </div>
      </article>
    </div>
    <?php
}
