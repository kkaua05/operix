<?php

use App\Models\Product;
use App\Notifications\CriticalStockDigestNotification;
use Illuminate\Support\Facades\Notification;

test('it notifies management with the critical products for a company', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    Product::factory()->create(['company_id' => $admin->company_id, 'stock_quantity' => 1, 'min_stock' => 10]);
    Product::factory()->create(['company_id' => $admin->company_id, 'stock_quantity' => 50, 'min_stock' => 5]);

    $this->artisan('stock:critical-digest')->assertSuccessful();

    Notification::assertSentTo($admin, CriticalStockDigestNotification::class, function ($notification) {
        return $notification->products->count() === 1;
    });
});

test('a company with no critical stock is not notified', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    Product::factory()->create(['company_id' => $admin->company_id, 'stock_quantity' => 50, 'min_stock' => 5]);

    $this->artisan('stock:critical-digest')->assertSuccessful();

    Notification::assertNothingSentTo($admin);
});

test('a company with no management users is skipped without error', function () {
    Notification::fake();

    $user = actingAsCompanyUser(['technician']);
    Product::factory()->create(['company_id' => $user->company_id, 'stock_quantity' => 1, 'min_stock' => 10]);

    $this->artisan('stock:critical-digest')->assertSuccessful();

    Notification::assertNothingSent();
});
