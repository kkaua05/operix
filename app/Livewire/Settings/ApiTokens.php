<?php

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Lets a user issue/revoke their own Sanctum personal access tokens
 * (§48) for external integrations against the /api/v1 endpoints — the
 * token carries the same permissions as the user (WorkOrderPolicy etc.
 * still apply, tenant scoping still applies via EnsureCompanyContext).
 */
#[Layout('components.layouts.app', ['title' => 'Tokens de API — Operix'])]
class ApiTokens extends Component
{
    public string $name = '';

    public ?string $plainTextToken = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function create(): void
    {
        $validated = $this->validate();

        $token = auth()->user()->createToken($validated['name']);

        $this->plainTextToken = $token->plainTextToken;
        $this->reset('name');
    }

    public function dismissToken(): void
    {
        $this->plainTextToken = null;
    }

    public function revoke(int $tokenId): void
    {
        auth()->user()->tokens()->whereKey($tokenId)->delete();
    }

    public function render(): View
    {
        return view('livewire.settings.api-tokens', [
            'tokens' => auth()->user()->tokens()->orderByDesc('created_at')->get(),
        ]);
    }
}
