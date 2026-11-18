<?php
class Producto
{
    public int $id;
    public string $nombre;
    public float $precio;
    public ?float $precioOferta;
    public int $stock;
    public bool $activo;
    public ?string $imagen;

    public function __construct(array $fila)
    {
        $this->id = (int) ($fila['id'] ?? 0);
        $this->nombre = (string) ($fila['nombre'] ?? '');
        $this->precio = (float) ($fila['precio'] ?? 0);
        $this->precioOferta = isset($fila['precio_oferta']) ? (float) $fila['precio_oferta'] : null;
        $this->stock = (int) ($fila['stock'] ?? 0);
        $this->activo = (bool) ($fila['activo'] ?? true);
        $this->imagen = $fila['imagen'] ?? null;
    }

    public function enOferta(): bool
    {
        return $this->precioOferta !== null && $this->precioOferta < $this->precio;
    }

    public function descuento(): int
    {
        if (!$this->enOferta() || $this->precio <= 0) {
            return 0;
        }
        return (int) round(100 * ($this->precio - $this->precioOferta) / $this->precio);
    }

    public function disponible(int $cantidad = 1): bool
    {
        return $this->activo && $this->stock >= $cantidad;
    }

    public static function sku(int $id): string
    {
        return 'BYL-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
