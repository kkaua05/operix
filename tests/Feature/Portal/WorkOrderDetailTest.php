<?php

use App\Enums\WorkOrderStatus;
use App\Livewire\Portal\WorkOrderDetail;
use App\Models\Technician;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('a technician cannot open a work order assigned to someone else', function () {
    $technician = actingAsTechnicianUser();
    $otherTechnician = Technician::factory()->create(['company_id' => $technician->company_id]);
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $otherTechnician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])->assertForbidden();
});

test('a technician can add and toggle checklist items', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    $component = Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->set('newChecklistItem', 'Testar conexão')
        ->call('addChecklistItem')
        ->assertHasNoErrors();

    $item = $workOrder->checklists()->first()->items()->first();

    expect($item->description)->toBe('Testar conexão')
        ->and($item->is_checked)->toBeFalse();

    $component->call('toggleChecklistItem', $item->id);

    expect($item->fresh()->is_checked)->toBeTrue();
});

test('saving the diagnosis updates the work order fields', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->set('diagnosis_category', 'Rede')
        ->set('diagnosis', 'Sem sinal de internet')
        ->set('cause', 'Cabo rompido')
        ->set('resolution', 'Cabo substituído')
        ->set('recommendation', 'Revisar canaleta externa')
        ->call('saveDiagnosis')
        ->assertHasNoErrors();

    $workOrder->refresh();

    expect($workOrder->diagnosis_category)->toBe('Rede')
        ->and($workOrder->diagnosis)->toBe('Sem sinal de internet')
        ->and($workOrder->cause)->toBe('Cabo rompido')
        ->and($workOrder->resolution)->toBe('Cabo substituído')
        ->and($workOrder->recommendation)->toBe('Revisar canaleta externa');
});

test('a technician can upload a photo as evidence', function () {
    Storage::fake('public');

    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->set('evidenceFile', UploadedFile::fake()->image('foto.jpg'))
        ->call('uploadEvidence')
        ->assertHasNoErrors();

    $attachment = $workOrder->attachments()->first();

    expect($attachment)->not->toBeNull()
        ->and($attachment->type)->toBe('photo');

    Storage::disk('public')->assertExists($attachment->file_path);
});

test('an executable file is rejected as evidence', function () {
    Storage::fake('public');

    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->set('evidenceFile', UploadedFile::fake()->create('malware.exe', 10))
        ->call('uploadEvidence')
        ->assertHasErrors(['evidenceFile']);

    expect($workOrder->attachments()->count())->toBe(0);
});

test('finishing is blocked while checklist items are pending', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    $component = Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->set('newChecklistItem', 'Item pendente')
        ->call('addChecklistItem')
        ->set('diagnosis', 'Problema')
        ->set('resolution', 'Solução')
        ->call('openFinishStage');

    $component->assertHasErrors(['finish'])
        ->assertSet('stage', 'working');
});

test('finishing is blocked without a diagnosis and resolution', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->call('openFinishStage')
        ->assertHasErrors(['finish'])
        ->assertSet('stage', 'working');
});

test('a technician can finish a work order with a signature, moving it to resolved', function () {
    Storage::fake('public');

    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    $pngDataUrl = 'data:image/png;base64,'.base64_encode(base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
    ));

    $component = Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->set('diagnosis', 'Problema')
        ->set('resolution', 'Solução')
        ->call('openFinishStage')
        ->assertSet('stage', 'finishing')
        ->set('signerName', 'Maria Cliente')
        ->set('signatureDataUrl', $pngDataUrl)
        ->call('submitSignature')
        ->assertHasNoErrors();

    $workOrder->refresh();

    expect($workOrder->status)->toBe(WorkOrderStatus::Resolved)
        ->and($workOrder->attachments()->where('type', 'signature')->exists())->toBeTrue();

    $signature = $workOrder->attachments()->where('type', 'signature')->first();
    expect($signature->signer_name)->toBe('Maria Cliente');

    $component->assertSet('stage', 'rating');
});

test('signature requires a signer name', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->set('diagnosis', 'Problema')
        ->set('resolution', 'Solução')
        ->call('openFinishStage')
        ->set('signatureDataUrl', 'data:image/png;base64,aGVsbG8=')
        ->call('submitSignature')
        ->assertHasErrors(['signerName']);

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::InProgress);
});

test('a customer rating can be submitted after resolving, or skipped', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Resolved->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->assertSet('stage', 'rating')
        ->set('ratingScore', 5)
        ->set('ratingComment', 'Ótimo atendimento')
        ->call('submitRating')
        ->assertSet('stage', 'done');

    $rating = $workOrder->fresh()->rating;

    expect($rating)->not->toBeNull()
        ->and($rating->score)->toBe(5)
        ->and($rating->comment)->toBe('Ótimo atendimento');
});

test('skipping the rating does not create a rating record', function () {
    $technician = actingAsTechnicianUser();
    $workOrder = createWorkOrderForCompany($technician->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Resolved->value,
    ]);

    Livewire::test(WorkOrderDetail::class, ['workOrder' => $workOrder])
        ->call('skipRating')
        ->assertSet('stage', 'done');

    expect($workOrder->fresh()->rating)->toBeNull();
});
