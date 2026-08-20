<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horário comercial
    |--------------------------------------------------------------------------
    |
    | Usado pelo SlaService quando a política de SLA tem business_hours_only
    | habilitado: minutos de contagem de SLA só correm dentro desta janela,
    | nos dias úteis definidos abaixo (0 = domingo ... 6 = sábado), pulando
    | os feriados cadastrados por empresa (tabela holidays).
    |
    */

    'business_hours' => [
        'start' => env('SLA_BUSINESS_HOURS_START', '08:00'),
        'end' => env('SLA_BUSINESS_HOURS_END', '18:00'),
    ],

    'business_days' => [1, 2, 3, 4, 5],

    /*
    |--------------------------------------------------------------------------
    | Limiares do indicador visual
    |--------------------------------------------------------------------------
    |
    | Percentual do tempo de SLA decorrido a partir do qual o status passa
    | para WARNING e depois CRITICAL, antes de virar BREACHED ao ultrapassar
    | o prazo.
    |
    */

    'thresholds' => [
        'warning' => 70,
        'critical' => 90,
    ],
];
