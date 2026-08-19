<?php

namespace App\Enums;

enum SlaStatus: string
{
    case Normal = 'normal';
    case Warning = 'warning';
    case Critical = 'critical';
    case Breached = 'breached';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Warning => 'Atenção',
            self::Critical => 'Crítico',
            self::Breached => 'Violado',
            self::Paused => 'Pausado',
        };
    }
}
