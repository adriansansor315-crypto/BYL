<?php
class Pedido
{
    public static function cancelar(int $pedidoId, ?int $usuarioId): bool
    {
        return (bool) transaccion(function () use ($pedidoId, $usuarioId) {
            $ped = fila('SELECT id, estado, cupon_id FROM pedidos WHERE id = ?', [$pedidoId]);
            if (!$ped || $ped['estado'] === 'cancelado' || $ped['estado'] === 'entregado') {
                return false;
            }
            foreach (filas('SELECT producto_id, cantidad FROM pedido_detalle WHERE pedido_id = ?', [$pedidoId]) as $r) {
                Inventario::mover((int) $r['producto_id'], (int) $r['cantidad'], 'cancelacion', 'pedido ' . $pedidoId, $usuarioId);
            }
            if ($ped['cupon_id'] !== null) {
                Cupon::liberar((int) $ped['cupon_id']);
            }
            ejecuta("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?", [$pedidoId]);
            return true;
        });
    }
}
