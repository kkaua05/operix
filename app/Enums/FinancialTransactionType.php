<?php

namespace App\Enums;

enum FinancialTransactionType: string
{
    case Revenue = 'revenue';
    case Cost = 'cost';

    public function label(): string
    {
        return match ($this) {
            self::Revenue => 'Receita',
            self::Cost => 'Custo',
        };
    }
}
