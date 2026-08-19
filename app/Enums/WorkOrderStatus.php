<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case New = 'new';
    case Triage = 'triage';
    case WaitingScheduling = 'waiting_scheduling';
    case Scheduled = 'scheduled';
    case Assigned = 'assigned';
    case EnRoute = 'en_route';
    case InProgress = 'in_progress';
    case WaitingCustomer = 'waiting_customer';
    case WaitingMaterial = 'waiting_material';
    case WaitingApproval = 'waiting_approval';
    case Resolved = 'resolved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nova',
            self::Triage => 'Triagem',
            self::WaitingScheduling => 'Aguardando agendamento',
            self::Scheduled => 'Agendada',
            self::Assigned => 'Atribuída',
            self::EnRoute => 'Em deslocamento',
            self::InProgress => 'Em atendimento',
            self::WaitingCustomer => 'Aguardando cliente',
            self::WaitingMaterial => 'Aguardando material',
            self::WaitingApproval => 'Aguardando aprovação',
            self::Resolved => 'Resolvida',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * Transições de status permitidas, usadas pelo WorkOrderStatusService (Fase 9)
     * para bloquear mudanças inválidas (ex.: Completed nunca volta direto para New).
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Triage, self::WaitingScheduling, self::Cancelled],
            self::Triage => [self::WaitingScheduling, self::Scheduled, self::Cancelled],
            self::WaitingScheduling => [self::Scheduled, self::Cancelled],
            self::Scheduled => [self::Assigned, self::Cancelled],
            self::Assigned => [self::EnRoute, self::Scheduled, self::Cancelled],
            self::EnRoute => [self::InProgress, self::Cancelled],
            self::InProgress => [self::WaitingCustomer, self::WaitingMaterial, self::WaitingApproval, self::Resolved, self::Cancelled],
            self::WaitingCustomer, self::WaitingMaterial, self::WaitingApproval => [self::InProgress, self::Cancelled],
            self::Resolved => [self::Completed, self::InProgress],
            self::Completed => [],
            self::Cancelled => [],
        };
    }
}
