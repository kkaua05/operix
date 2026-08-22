<?php

namespace App\Livewire\Portal;

use App\Enums\WorkOrderStatus;
use App\Exceptions\InvalidWorkOrderStatusTransitionException;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklist;
use App\Services\WorkOrderStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The field-execution hub for a single OS (§27-31): checklist, diagnóstico,
 * evidências, and — when finishing — a signature capture and the customer
 * rating collected on the spot. Every action re-checks that the OS still
 * belongs to the logged-in technician, the same guard as MyWorkOrders.
 */
#[Layout('components.layouts.portal')]
class WorkOrderDetail extends Component
{
    use WithFileUploads;

    public WorkOrder $workOrder;

    public string $stage = 'working';

    public string $newChecklistItem = '';

    public string $diagnosis_category = '';

    public string $diagnosis = '';

    public string $cause = '';

    public string $resolution = '';

    public string $recommendation = '';

    /** @var TemporaryUploadedFile|null */
    public $evidenceFile = null;

    public string $signatureDataUrl = '';

    public string $signerName = '';

    public string $signerDocument = '';

    public int $ratingScore = 5;

    public string $ratingComment = '';

    public function mount(WorkOrder $workOrder): void
    {
        $technician = auth()->user()->technician;
        abort_unless($technician !== null, 403);
        abort_unless($workOrder->technician_id === $technician->id, 403);

        $this->workOrder = $workOrder;
        $this->diagnosis_category = (string) $workOrder->diagnosis_category;
        $this->diagnosis = (string) $workOrder->diagnosis;
        $this->cause = (string) $workOrder->cause;
        $this->resolution = (string) $workOrder->resolution;
        $this->recommendation = (string) $workOrder->recommendation;

        if ($workOrder->status === WorkOrderStatus::Resolved) {
            $this->stage = $workOrder->rating ? 'done' : 'rating';
        }
    }

    protected function checklist(): WorkOrderChecklist
    {
        return $this->workOrder->checklists()->firstOrCreate(
            ['name' => 'Checklist de execução'],
            ['is_required' => true],
        );
    }

    public function addChecklistItem(): void
    {
        $this->validate(['newChecklistItem' => ['required', 'string', 'max:255']]);

        $this->checklist()->items()->create(['description' => $this->newChecklistItem]);

        $this->reset('newChecklistItem');
    }

    public function toggleChecklistItem(int $itemId): void
    {
        $item = $this->checklist()->items()->findOrFail($itemId);

        $item->update([
            'is_checked' => ! $item->is_checked,
            'checked_by' => ! $item->is_checked ? auth()->id() : null,
            'checked_at' => ! $item->is_checked ? now() : null,
        ]);
    }

    public function saveDiagnosis(): void
    {
        $validated = $this->validate([
            'diagnosis_category' => ['nullable', 'string', 'max:100'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'cause' => ['nullable', 'string', 'max:2000'],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'recommendation' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workOrder->update($validated);

        $this->dispatch('diagnosis-saved');
    }

    public function uploadEvidence(): void
    {
        $this->validate([
            'evidenceFile' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,heic,mp4,mov,pdf'],
        ]);

        $path = $this->evidenceFile->store('work-orders/'.$this->workOrder->id.'/evidence', 'public');

        $type = match (true) {
            str_starts_with((string) $this->evidenceFile->getMimeType(), 'image/') => 'photo',
            str_starts_with((string) $this->evidenceFile->getMimeType(), 'video/') => 'video',
            default => 'document',
        };

        $this->workOrder->attachments()->create([
            'uploaded_by' => auth()->id(),
            'type' => $type,
            'file_path' => $path,
            'file_name' => $this->evidenceFile->getClientOriginalName(),
            'mime_type' => $this->evidenceFile->getMimeType(),
            'size_bytes' => $this->evidenceFile->getSize(),
        ]);

        $this->reset('evidenceFile');
    }

    public function deleteEvidence(int $attachmentId): void
    {
        $attachment = $this->workOrder->attachments()->where('type', '!=', 'signature')->findOrFail($attachmentId);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }

    public function openFinishStage(): void
    {
        $requiredPending = $this->checklist()->items()->where('is_checked', false)->exists();

        if ($requiredPending) {
            $this->addError('finish', 'Conclua todos os itens do checklist antes de finalizar.');

            return;
        }

        if ($this->diagnosis === '' || $this->resolution === '') {
            $this->addError('finish', 'Preencha ao menos o problema identificado e a solução aplicada antes de finalizar.');

            return;
        }

        $this->stage = 'finishing';
    }

    public function submitSignature(WorkOrderStatusService $statusService): void
    {
        $this->validate([
            'signerName' => ['required', 'string', 'max:255'],
            'signerDocument' => ['nullable', 'string', 'max:20'],
            'signatureDataUrl' => ['required', 'string', 'starts_with:data:image/png;base64,'],
        ]);

        $encoded = substr($this->signatureDataUrl, strlen('data:image/png;base64,'));
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            $this->addError('signatureDataUrl', 'Assinatura inválida. Tente novamente.');

            return;
        }

        $path = 'work-orders/'.$this->workOrder->id.'/evidence/assinatura-'.now()->timestamp.'.png';
        Storage::disk('public')->put($path, $binary);

        $this->workOrder->attachments()->create([
            'uploaded_by' => auth()->id(),
            'type' => 'signature',
            'file_path' => $path,
            'file_name' => 'assinatura.png',
            'mime_type' => 'image/png',
            'size_bytes' => strlen($binary),
            'signer_name' => $this->signerName,
            'signer_document' => $this->signerDocument,
            'ip_address' => request()->ip(),
        ]);

        try {
            $statusService->transition($this->workOrder, WorkOrderStatus::Resolved, auth()->user());
        } catch (InvalidWorkOrderStatusTransitionException $e) {
            $this->addError('finish', $e->getMessage());

            return;
        }

        $this->stage = 'rating';
    }

    public function submitRating(): void
    {
        $this->validate([
            'ratingScore' => ['required', 'integer', 'min:1', 'max:5'],
            'ratingComment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workOrder->rating()->create([
            'company_id' => $this->workOrder->company_id,
            'customer_id' => $this->workOrder->customer_id,
            'technician_id' => $this->workOrder->technician_id,
            'score' => $this->ratingScore,
            'comment' => $this->ratingComment,
        ]);

        $this->stage = 'done';
    }

    public function skipRating(): void
    {
        $this->stage = 'done';
    }

    public function render(): View
    {
        $this->workOrder->load(['customer', 'address']);

        return view('livewire.portal.work-order-detail', [
            'checklistItems' => $this->checklist()->items()->orderBy('id')->get(),
            'evidenceAttachments' => $this->workOrder->attachments()->where('type', '!=', 'signature')->orderByDesc('created_at')->get(),
        ]);
    }
}
