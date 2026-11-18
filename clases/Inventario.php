<?php
class Inventario
{
    public static function mover(int $productoId, int $delta, string $tipo, ?string $referencia, ?int $usuarioId): bool
    {
        ejecuta('CALL sp_mover_stock(?, ?, ?, ?, ?, @ok)', [$productoId, $delta, $tipo, $referencia, $usuarioId]);
        return (int) fila('SELECT @ok AS ok')['ok'] === 1;
    }
}
