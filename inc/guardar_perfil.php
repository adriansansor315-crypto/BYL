<?php
function leer_perfil(array $p): array
{
    $letras = '/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,80}$/u';
    $d = [
        'nombre' => trim(preg_replace('/\s+/', ' ', $p['nombre'] ?? '')),
        'telefono' => trim($p['telefono'] ?? ''),
        'fecha_nacimiento' => trim($p['fecha_nacimiento'] ?? ''),
        'ciudad' => trim($p['ciudad'] ?? ''),
        'genero' => $p['genero'] ?? '',
        'direccion' => trim($p['direccion'] ?? ''),
        'frecuencia_compra' => trim((string) ($p['frecuencia_compra'] ?? '')),
        'color_favorito' => strtoupper(trim($p['color_favorito'] ?? '')),
        'hora_contacto' => trim($p['hora_contacto'] ?? ''),
        'recibir_promos' => !empty($p['recibir_promos']) ? 1 : 0,
        'correo' => mb_strtolower(trim($p['correo'] ?? '')),
        'intereses' => array_map('intval', (array) ($p['intereses'] ?? [])),
    ];
    $e = [];
    if (!preg_match($letras, $d['nombre'])) $e[] = 'El nombre solo puede tener letras y espacios (mínimo 3).';
    if (!preg_match('/^[0-9]{10}$/', $d['telefono'])) $e[] = 'El teléfono debe tener exactamente 10 números.';
    $fecha = DateTime::createFromFormat('Y-m-d', $d['fecha_nacimiento']);
    if (!$fecha || $fecha->format('Y-m-d') !== $d['fecha_nacimiento'] || $d['fecha_nacimiento'] > date('Y-m-d') || $d['fecha_nacimiento'] < '1920-01-01') $e[] = 'La fecha de nacimiento no es válida.';
    if (!in_array($d['ciudad'], ['Tizimín', 'Valladolid', 'Espita', 'Panabá', 'Río Lagartos', 'Mérida', 'Otra'], true)) $e[] = 'Elige tu ciudad.';
    if (!in_array($d['genero'], ['mujer', 'hombre', 'sin_decir'], true)) $e[] = 'Elige una opción de género.';
    if ($d['direccion'] === '' || mb_strlen($d['direccion']) > 200) $e[] = 'Escribe tu dirección (máximo 200 caracteres).';
    if (!preg_match('/^[0-9]{1,2}$/', $d['frecuencia_compra']) || (int) $d['frecuencia_compra'] > 30) $e[] = 'Las compras al mes deben ser un número entre 0 y 30.';
    if (!preg_match('/^#[0-9A-F]{6}$/', $d['color_favorito'])) $e[] = 'Elige un color válido.';
    if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $d['hora_contacto'])) $e[] = 'Elige una hora válida.';
    if (!preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $d['correo']) || strlen($d['correo']) > 120) $e[] = 'El correo debe tener el formato usuario@dominio.com.';
    $validas = array_map('intval', array_column(filas('SELECT id FROM categorias'), 'id'));
    $d['intereses'] = array_values(array_intersect($d['intereses'], $validas));
    return [$d, $e];
}

function guardar_intereses(int $uid, array $ids): void
{
    ejecuta('DELETE FROM usuario_interes WHERE usuario_id = ?', [$uid]);
    foreach ($ids as $c) {
        ejecuta('INSERT INTO usuario_interes (usuario_id, categoria_id) VALUES (?, ?)', [$uid, $c]);
    }
}
