<?php
class Cupon
{
    public static function validar(string $codigo, float $subtotal): array
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return [null, 0.0, 'Escribe el código del cupón.'];
        }
        $c = fila('SELECT * FROM cupones WHERE codigo = ?', [$codigo]);
        if (!$c || !(int) $c['activo']) {
            return [null, 0.0, 'Ese cupón no existe o ya no está activo.'];
        }
        if ($c['vigencia_hasta'] !== null && $c['vigencia_hasta'] < date('Y-m-d')) {
            return [null, 0.0, 'Ese cupón ya venció.'];
        }
        if ($c['usos_max'] !== null && (int) $c['usos'] >= (int) $c['usos_max']) {
            return [null, 0.0, 'Ese cupón ya se usó el máximo de veces.'];
        }
        if ($subtotal < (float) $c['minimo_compra']) {
            return [null, 0.0, 'Este cupón aplica en compras desde ' . dinero($c['minimo_compra']) . '.'];
        }
        $desc = $c['tipo'] === 'porcentaje' ? round($subtotal * (float) $c['valor'] / 100, 2) : min((float) $c['valor'], $subtotal);
        return [$c, $desc, ''];
    }

    public static function liberar(int $cuponId): void
    {
        ejecuta('UPDATE cupones SET usos = usos - 1 WHERE id = ? AND usos > 0', [$cuponId]);
    }
}
