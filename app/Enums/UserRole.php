<?php

namespace App\Enums;

/**
 * Nomes canônicos dos papéis padrão do sistema (seção 10 do spec).
 * As roles em si são gerenciadas via spatie/laravel-permission (DB-driven,
 * escopadas por company_id); este enum existe para evitar strings soltas
 * ao referenciar os papéis padrão em seeders, policies e testes.
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Dispatcher = 'dispatcher';
    case Technician = 'technician';
    case Financial = 'financial';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Administrador',
            self::Manager => 'Gestor',
            self::Dispatcher => 'Despachante',
            self::Technician => 'Técnico',
            self::Financial => 'Financeiro',
            self::Support => 'Suporte',
        };
    }
}
