<?php

use App\Models\Technician;
use Laravel\Sanctum\Sanctum;

test('an unauthenticated request is rejected', function () {
    $this->getJson('/api/v1/technicians')->assertUnauthorized();
});

test('it lists only the current company technicians and filters by status', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $mine = Technician::factory()->create(['company_id' => $user->company_id, 'status' => 'available']);
    Technician::factory()->create(['company_id' => $user->company_id, 'status' => 'offline']);
    $other = Technician::factory()->create();

    $response = $this->getJson('/api/v1/technicians?status=available')->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toEqual(collect([$mine->id]))
        ->and($ids)->not->toContain($other->id);
});

test('a user cannot view a technician from another company', function () {
    $user = actingAsCompanyUser(['admin']);
    Sanctum::actingAs($user, ['*']);

    $other = Technician::factory()->create();

    $this->getJson("/api/v1/technicians/{$other->id}")->assertNotFound();
});
