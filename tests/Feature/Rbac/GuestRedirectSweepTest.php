<?php

/**
 * Regression net (§52): every route below must sit behind the 'auth'
 * middleware. A future phase adding a new top-level page and forgetting
 * to nest it inside the authenticated route group would otherwise only
 * be caught by chance — this sweeps every parameterless GET route in the
 * app (route:list) and asserts a guest is redirected to /login on all of
 * them, in one place instead of one assertion buried in each module's
 * own test file.
 */
test('every protected page redirects a guest to the login screen', function (string $uri) {
    $this->get('/'.$uri)->assertRedirect('/login');
})->with([
    'audit',
    'customers',
    'customers/create',
    'dashboard',
    'dispatch',
    'financial',
    'inventory/categories',
    'inventory/products',
    'inventory/products/create',
    'inventory/suppliers',
    'inventory/suppliers/create',
    'portal',
    'profile',
    'reports',
    'scheduling',
    'scheduling/create',
    'settings/api-tokens',
    'settings/notifications',
    'teams',
    'teams/create',
    'technicians',
    'technicians/create',
    'users',
    'users/create',
    'work-orders',
    'work-orders/create',
]);
