<?php

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\WorkOrder;
use Laravel\Sanctum\Sanctum;

test('an unauthenticated request is rejected', function () {
    $this->getJson('/api/v1/work-orders')->assertUnauthorized();
});

test('it lists only the current company work orders', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $mine = createWorkOrderForCompany($user->company_id);
    $notMine = WorkOrder::factory()->create();

    $response = $this->getJson('/api/v1/work-orders')->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($mine->id)
        ->and($ids)->not->toContain($notMine->id);
});

test('it filters by status and priority', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $urgent = createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value, 'priority' => WorkOrderPriority::Urgent->value]);
    createWorkOrderForCompany($user->company_id, ['status' => WorkOrderStatus::New->value, 'priority' => WorkOrderPriority::Low->value]);

    $response = $this->getJson('/api/v1/work-orders?priority=urgent')->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toEqual(collect([$urgent->id]));
});

test('it sorts and paginates results', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    createWorkOrderForCompany($user->company_id, ['created_at' => now()->subDays(2)]);
    $newest = createWorkOrderForCompany($user->company_id, ['created_at' => now()]);

    $response = $this->getJson('/api/v1/work-orders?sort=-created_at&per_page=1')->assertOk();

    expect($response->json('data.0.id'))->toBe($newest->id)
        ->and($response->json('meta.per_page'))->toBe(1);
});

test('a user cannot view a work order from another company', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $other = WorkOrder::factory()->create();

    $this->getJson("/api/v1/work-orders/{$other->id}")->assertNotFound();
});

test('a user with work_orders.create permission can create a work order via the api', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    $response = $this->postJson('/api/v1/work-orders', [
        'customer_id' => $customer->id,
        'priority' => 'high',
        'description' => 'Criada via API',
    ])->assertCreated();

    expect($response->json('data.priority'))->toBe('high')
        ->and(WorkOrder::where('description', 'Criada via API')->exists())->toBeTrue();
});

test('a user without work_orders.create permission cannot create a work order', function () {
    $user = actingAsCompanyUser(['technician']);
    Sanctum::actingAs($user, ['*']);

    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    $this->postJson('/api/v1/work-orders', [
        'customer_id' => $customer->id,
        'priority' => 'high',
    ])->assertForbidden();
});

test('creating a work order validates required fields', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/work-orders', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id', 'priority']);
});
