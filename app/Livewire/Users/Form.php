<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\AuditService;
use App\Support\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public string $status = 'active';

    public string $password = '';

    public string $role = '';

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            $this->authorize('update', $user);

            $this->user = $user;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone;
            $this->status = $user->status;
            $this->role = $user->getRoleNames()->first() ?? '';
        } else {
            $this->authorize('create', User::class);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$this->user ? 'nullable' : 'required', 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(Permissions::ROLE_PERMISSIONS))],
        ];
    }

    public function save(AuditService $auditService): void
    {
        $validated = $this->validate();

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
        ];

        if ($this->user) {
            $oldRole = $this->user->getRoleNames()->first();

            $this->user->update($attributes);

            if ($validated['password'] !== '') {
                $this->user->update(['password' => Hash::make($validated['password'])]);
            }

            if ($oldRole !== $validated['role']) {
                $this->user->syncRoles([$validated['role']]);
                $auditService->log(
                    'user.role_changed',
                    $this->user,
                    ['role' => $oldRole],
                    ['role' => $validated['role']],
                    auth()->user(),
                );
            }

            $auditService->log('user.updated', $this->user, null, $attributes, auth()->user());

            session()->flash('status', 'Usuário atualizado com sucesso.');
        } else {
            $attributes['company_id'] = auth()->user()->company_id;
            $attributes['password'] = Hash::make($validated['password']);

            $this->user = User::create($attributes);
            $this->user->assignRole($validated['role']);

            $auditService->log('user.created', $this->user, null, [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $validated['role'],
            ], auth()->user());

            session()->flash('status', 'Usuário cadastrado com sucesso.');
        }

        $this->redirectRoute('users.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.users.form', ['roles' => array_keys(Permissions::ROLE_PERMISSIONS)]);
    }
}
