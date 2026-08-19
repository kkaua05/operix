<?php

namespace App\Enums;

enum TechnicianStatus: string
{
    case Available = 'available';
    case Busy = 'busy';
    case EnRoute = 'en_route';
    case InService = 'in_service';
    case OnBreak = 'on_break';
    case Offline = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::Busy => 'Ocupado',
            self::EnRoute => 'Em deslocamento',
            self::InService => 'Em atendimento',
            self::OnBreak => 'Em pausa',
            self::Offline => 'Offline',
        };
    }
}
