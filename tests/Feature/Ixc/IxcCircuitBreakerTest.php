<?php

use App\Services\Ixc\IxcCircuitBreaker;

beforeEach(function () {
    config(['ixc.circuit_breaker.max_failures' => 3, 'ixc.circuit_breaker.cooldown_minutes' => 30]);
    $this->breaker = new IxcCircuitBreaker;
    $this->breaker->reset();
});

test('the circuit starts closed', function () {
    expect($this->breaker->isOpen())->toBeFalse();
});

test('the circuit stays closed below the failure threshold', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();

    expect($this->breaker->isOpen())->toBeFalse();
});

test('the circuit opens once the failure threshold is reached', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();

    expect($this->breaker->isOpen())->toBeTrue()
        ->and($this->breaker->openUntil())->not->toBeNull();
});

test('a success clears the failure count entirely, not just decrements it', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    $this->breaker->recordSuccess();
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();

    // Two failures again after the reset — still below the threshold of 3.
    expect($this->breaker->isOpen())->toBeFalse();
});

test('reset closes an open circuit immediately', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    expect($this->breaker->isOpen())->toBeTrue();

    $this->breaker->reset();

    expect($this->breaker->isOpen())->toBeFalse();
});
