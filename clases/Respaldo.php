<?php
class Respaldo
{
    const TABLAS = ['roles', 'usuarios', 'categorias', 'marcas', 'etiquetas', 'cupones', 'productos', 'producto_etiqueta',
        'carrito_items', 'deseos', 'pedidos', 'pedido_detalle', 'resenas', 'movimientos_stock', 'promociones', 'usuario_interes'];

    public static function generar(): string
    {
        $sql = "SET FOREIGN_KEY_CHECKS=0;\n";
        foreach (array_reverse(self::TABLAS) as $t) {
            $sql .= "DELETE FROM `$t`;\n";
        }
        foreach (self::TABLAS as $t) {
            foreach (filas("SELECT * FROM `$t`") as $f) {
                $cols = '`' . implode('`, `', array_keys($f)) . '`';
                $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . escapar((string) $v) . "'", array_values($f));
                $sql .= "INSERT INTO `$t` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
            }
        }
        return $sql . "SET FOREIGN_KEY_CHECKS=1;\n";
    }

    public static function restaurar(string $texto): array
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
        $lineas = array_values(array_filter(preg_split('/\r\n|\n|\r/', $texto), fn($l) => trim($l) !== ''));
        if (!$lineas || $lineas[0] !== 'SET FOREIGN_KEY_CHECKS=0;' || end($lineas) !== 'SET FOREIGN_KEY_CHECKS=1;') {
            return [false, 'Ese archivo no es un respaldo de BYL.'];
        }
        $tablas = implode('|', self::TABLAS);
        $patron = '/^(SET FOREIGN_KEY_CHECKS=[01];|DELETE FROM `(' . $tablas . ')`;|INSERT INTO `(' . $tablas . ')` \((`\w+`(, `\w+`)*)\) VALUES \(.*\);)$/s';
        foreach ($lineas as $n => $l) {
            if (!preg_match($patron, $l)) {
                return [false, 'El archivo tiene una instrucción no permitida en la línea ' . ($n + 1) . '.'];
            }
        }
        try {
            transaccion(function () use ($lineas) {
                foreach ($lineas as $l) {
                    ejecuta($l);
                }
            });
        } catch (Throwable $e) {
            error_log($e->getMessage());
            return [false, 'No se pudo restaurar: el archivo no coincide con la estructura actual.'];
        }
        return [true, count($lineas) . ' instrucciones aplicadas.'];
    }
}
