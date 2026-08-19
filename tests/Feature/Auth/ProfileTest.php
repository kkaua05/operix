<?php

use App\Livewire\Profile\UpdatePassword;
use App\Livewire\Profile\UpdateProfileInformation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('profile page requires authentication', function () {
    $this->get('/profile')->assertRedirect('/login');
});

test('profile page renders for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(UpdateProfileInformation::class)
        ->set('name', 'Novo Nome')
        ->set('email', 'novo@example.com')
        ->set('phone', '11999999999')
        ->call('updateProfileInformation');

    $user->refresh();

    expect($user->name)->toBe('Novo Nome')
        ->and($user->email)->toBe('novo@example.com')
        ->and($user->phone)->toBe('11999999999');
});

test('password can be updated from the profile page', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    Livewire::actingAs($user)
        ->test(UpdatePassword::class)
        ->set('current_password', 'old-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('updating the password requires the correct current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    Livewire::actingAs($user)
        ->test(UpdatePassword::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors('current_password');
});
