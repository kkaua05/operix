<?php

use App\Livewire\Settings\ApiTokens;
use Livewire\Livewire;

test('a user can create a personal access token', function () {
    $user = actingAsCompanyUser(['admin']);

    Livewire::test(ApiTokens::class)
        ->set('name', 'Integração ERP')
        ->call('create')
        ->assertHasNoErrors()
        ->assertSet('plainTextToken', fn ($token) => $token !== null);

    expect($user->tokens()->where('name', 'Integração ERP')->exists())->toBeTrue();
});

test('a token name is required', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(ApiTokens::class)
        ->call('create')
        ->assertHasErrors(['name']);
});

test('a user can revoke their own token', function () {
    $user = actingAsCompanyUser(['admin']);
    $token = $user->createToken('Para revogar');

    Livewire::test(ApiTokens::class)->call('revoke', $token->accessToken->id);

    expect($user->tokens()->where('name', 'Para revogar')->exists())->toBeFalse();
});

test('the api actually accepts a token created through this page', function () {
    $user = actingAsCompanyUser(['admin']);

    $plainTextToken = null;
    Livewire::test(ApiTokens::class)
        ->set('name', 'Teste real')
        ->call('create')
        ->tap(function ($component) use (&$plainTextToken) {
            $plainTextToken = $component->get('plainTextToken');
        });

    $this->withHeaders(['Authorization' => "Bearer {$plainTextToken}"])
        ->getJson('/api/v1/customers')
        ->assertOk();
});
