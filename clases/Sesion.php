<?php
class Sesion
{
    private static $actual = false;
    private static $deseos = null;

    public static function usuario(): ?array
    {
        if (self::$actual === false) {
            self::$actual = null;
            if (!empty($_SESSION['uid'])) {
                self::$actual = fila('SELECT u.id, u.usuario, u.correo, u.nombre, u.telefono, u.foto, r.nombre AS rol
                    FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ? AND u.activo = 1', [(int) $_SESSION['uid']]);
                if (!self::$actual) {
                    unset($_SESSION['uid']);
                }
            }
        }
        return self::$actual;
    }

    public static function requiereLogin(?string $volver = null): array
    {
        $u = self::usuario();
        if (!$u) {
            mensaje('info', 'Inicia sesión para continuar.');
            redirigir('login.php?volver=' . urlencode($volver ?? ruta_actual()));
        }
        return $u;
    }

    public static function esLocal(): bool
    {
        return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    }

    public static function requiereRol(array $roles): array
    {
        $u = self::usuario();
        if (!$u) {
            redirigir('admin/acceso.php?volver=' . urlencode(ruta_actual()));
        }
        if (!in_array($u['rol'], $roles, true) || ($u['rol'] === 'sistemas' && SISTEMAS_SOLO_LOCAL && !self::esLocal())) {
            mensaje('danger', 'No tienes permiso para entrar ahí.');
            redirigir($u['rol'] === 'cliente' ? 'index.php' : 'admin/index.php');
        }
        return $u;
    }

    public static function deseos(): array
    {
        if (self::$deseos === null) {
            $u = self::usuario();
            self::$deseos = $u ? array_map('intval', array_column(filas('SELECT producto_id FROM deseos WHERE usuario_id = ?', [$u['id']]), 'producto_id')) : [];
        }
        return self::$deseos;
    }
}
