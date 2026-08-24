<?php

use App\Models\Customer;
use Laravel\Sanctum\Sanctum;

test('an unauthenticated request is rejected', function () {
    $this->getJson('/api/v1/customers')->assertUnauthorized();
});

test('it lists only the current company customers and supports search', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $mine = Customer::factory()->create(['company_id' => $user->company_id, 'name' => 'Cliente Alfa']);
    $other = Customer::factory()->create(['name' => 'Cliente Beta']);

    $response = $this->getJson('/api/v1/customers?search=Alfa')->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($mine->id)
        ->and($ids)->not->toContain($other->id);
});

test('a user cannot view a customer from another company', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $other = Customer::factory()->create();

    $this->getJson("/api/v1/customers/{$other->id}")->assertNotFound();
});

test('a user without customers.view is forbidden', function () {
    $user = actingAsCompanyUser([]);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/customers')->assertForbidden();
});
