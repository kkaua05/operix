<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Consumption = 'consumption';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrada',
            self::Out => 'Saída',
            self::Adjustment => 'Ajuste',
            self::Consumption => 'Consumo',
            self::Return => 'Devolução',
        };
    }
}
