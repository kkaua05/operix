<?php

use App\Models\Product;
use App\Services\AuditService;

test('log records the action, auditable, actor, and value diffs', function () {
    $admin = actingAsCompanyUser(['admin']);
    $product = Product::factory()->create(['company_id' => $admin->company_id]);

    $log = app(AuditService::class)->log(
        'product.updated',
        $product,
        ['name' => 'Nome antigo'],
        ['name' => 'Nome novo'],
        $admin,
    );

    expect($log->action)->toBe('product.updated')
        ->and($log->auditable_type)->toBe($product->getMorphClass())
        ->and($log->auditable_id)->toBe($product->id)
        ->and($log->old_values)->toBe(['name' => 'Nome antigo'])
        ->and($log->new_values)->toBe(['name' => 'Nome novo'])
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->company_id)->toBe($admin->company_id);
});

test('log works without an auditable model or values', function () {
    $admin = actingAsCompanyUser(['admin']);

    $log = app(AuditService::class)->log('auth.login', user: $admin);

    expect($log->auditable_type)->toBeNull()
        ->and($log->auditable_id)->toBeNull()
        ->and($log->old_values)->toBeNull()
        ->and($log->new_values)->toBeNull();
});
