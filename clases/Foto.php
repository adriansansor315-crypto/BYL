<?php
class Foto
{
    public static function esValida(string $nombre): bool
    {
        return (bool) preg_match('/^[a-z0-9\-]+\.(jpg|png|webp)$/', $nombre);
    }

    public static function guardar(array $f, ?string &$error, string $carpeta = 'productos'): ?string
    {
        if ($f['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
            $error = 'La foto no se pudo subir (máximo 2 MB).';
            return null;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset($tipos[$mime])) {
            $error = 'La foto debe ser JPG, PNG o WEBP.';
            return null;
        }
        $nombre = bin2hex(random_bytes(8)) . '.' . $tipos[$mime];
        if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/../imgs/' . $carpeta . '/' . $nombre)) {
            $error = 'No se pudo guardar la foto en el servidor.';
            return null;
        }
        return $nombre;
    }

    public static function borrar(?string $nombre): void
    {
        if ($nombre && self::esValida($nombre) && !fila('SELECT id FROM productos WHERE imagen = ?', [$nombre])) {
            @unlink(__DIR__ . '/../imgs/productos/' . $nombre);
        }
    }
}
