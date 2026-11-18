<?php
defined('DB_HOST')    || define('DB_HOST', '127.0.0.1');
defined('DB_PORT')    || define('DB_PORT', 3306);
defined('DB_USER')    || define('DB_USER', 'byl_web');
defined('DB_PASS')    || define('DB_PASS', 'Byl#2026');
defined('DB_NAME')    || define('DB_NAME', 'BYL');
defined('TIENDA')     || define('TIENDA', 'Bella y Limpia');
defined('WHATSAPP')   || define('WHATSAPP', '');
defined('POR_PAGINA') || define('POR_PAGINA', 12);
defined('STOCK_BAJO') || define('STOCK_BAJO', 5);
defined('SISTEMAS_SOLO_LOCAL') || define('SISTEMAS_SOLO_LOCAL', true);

$dir_script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if (substr($dir_script, -6) === '/admin') {
    $dir_script = substr($dir_script, 0, -6);
}
define('BASE', rtrim($dir_script, '/'));

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli
{
    static $conexion = null;
    if ($conexion === null) {
        try {
            $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $conexion->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            exit('No se pudo conectar a la base de datos. Revisa que MySQL esté encendido, que hayas importado BYL.sql y que el puerto en inc/conexion.php sea el correcto.');
        }
    }
    return $conexion;
}

function consulta(string $sql, array $params = []): mysqli_stmt
{
    $st = db()->prepare($sql);
    if ($params) {
        $tipos = '';
        foreach ($params as $v) {
            $tipos .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
        }
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    return $st;
}

function filas(string $sql, array $params = []): array
{
    $st = consulta($sql, $params);
    $filas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $filas;
}

function fila(string $sql, array $params = []): ?array
{
    return filas($sql, $params)[0] ?? null;
}

function ejecuta(string $sql, array $params = []): int
{
    $st = consulta($sql, $params);
    $n = $st->affected_rows;
    $st->close();
    return $n;
}

function ultimo_id(): int
{
    return (int) db()->insert_id;
}

function transaccion(callable $trabajo)
{
    $c = db();
    $c->begin_transaction();
    try {
        $resultado = $trabajo();
        $c->commit();
        return $resultado;
    } catch (Throwable $e) {
        $c->rollback();
        throw $e;
    }
}

function escapar(string $texto): string
{
    return db()->real_escape_string($texto);
}

function llama(string $procedimiento, array $argumentos = []): array
{
    $c = db();
    $lista = implode(', ', array_map(fn($a) => is_int($a) ? (string) $a : "'" . $c->real_escape_string((string) $a) . "'", $argumentos));
    $r = $c->query("CALL $procedimiento($lista)");
    $filas = $r instanceof mysqli_result ? $r->fetch_all(MYSQLI_ASSOC) : [];
    if ($r instanceof mysqli_result) {
        $r->free();
    }
    while ($c->more_results()) {
        $c->next_result();
        if ($extra = $c->store_result()) {
            $extra->free();
        }
    }
    return $filas;
}
