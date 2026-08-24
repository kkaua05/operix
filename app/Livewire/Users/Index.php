<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Usuários — Operix'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'busca', history: true)]
    public string $search = '';

    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $userId): void
    {
        $this->confirmingDeleteId = $userId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(AuditService $auditService): void
    {
        $user = User::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $user);

        $auditService->log('user.deleted', $user, ['name' => $user->name, 'email' => $user->email], null, auth()->user());

        $user->delete();

        $this->confirmingDeleteId = null;
        $this->resetPage();

        session()->flash('status', 'Usuário excluído com sucesso.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.users.index', ['users' => $users]);
    }
}
