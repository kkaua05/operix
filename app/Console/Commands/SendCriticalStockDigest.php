<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Product;
use App\Notifications\CriticalStockDigestNotification;
use App\Support\NotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily digest (§38) of products below their minimum stock, one
 * notification per company sent to admin+manager — scheduled once a day,
 * so no internal dedup is needed: running it again only re-sends if the
 * scheduler itself runs twice, which it doesn't.
 */
class SendCriticalStockDigest extends Command
{
    protected $signature = 'stock:critical-digest';

    protected $description = 'Notify company management about products below their minimum stock level';

    public function handle(): int
    {
        $companiesNotified = 0;

        Company::query()->where('status', 'active')->each(function (Company $company) use (&$companiesNotified) {
            $criticalProducts = Product::withoutCompanyScope()
                ->where('company_id', $company->id)
                ->whereColumn('stock_quantity', '<', 'min_stock')
                ->orderBy('name')
                ->get();

            if ($criticalProducts->isEmpty()) {
                return;
            }

            $recipients = NotificationRecipients::management($company->id);

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new CriticalStockDigestNotification($criticalProducts));
            $companiesNotified++;
        });

        $this->info("Sent critical stock digest to {$companiesNotified} compan".($companiesNotified === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }
}
