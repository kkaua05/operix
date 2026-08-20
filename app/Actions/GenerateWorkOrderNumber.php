<?php

namespace App\Actions;

use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generates the next sequential work order number for a company
 * (e.g. OS-00001, OS-00002...), locking the row to avoid a race
 * between two concurrent creations landing on the same number.
 */
class GenerateWorkOrderNumber
{
    public function handle(int $companyId): string
    {
        return DB::transaction(function () use ($companyId) {
            $last = WorkOrder::withoutCompanyScope()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('number');

            $nextSequence = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

            return 'OS-'.Str::padLeft((string) $nextSequence, 5, '0');
        });
    }
}
