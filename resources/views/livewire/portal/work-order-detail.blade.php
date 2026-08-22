<div>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-semibold">{{ $workOrder->number }}</h1>
            <p class="text-xs text-op-secondary">{{ $workOrder->customer->name }}</p>
        </div>
        <a href="{{ route('portal.index') }}" wire:navigate class="text-xs text-op-secondary hover:text-op-primary">
            ← Minhas Ordens
        </a>
    </div>

    @if ($stage === 'working')
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <x-status-badge :status="$workOrder->status" />
                <x-priority-badge :priority="$workOrder->priority" />
            </div>

            @error('finish')
                <x-alert variant="danger">{{ $message }}</x-alert>
            @enderror

            {{-- Checklist --}}
            <div class="rounded-xl border border-op-border bg-op-card p-4">
                <h2 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Checklist</h2>

                @if ($checklistItems->isEmpty())
                    <p class="mb-3 text-xs text-op-secondary">Nenhum item adicionado ainda.</p>
                @else
                    <div class="mb-3 space-y-2">
                        @foreach ($checklistItems as $item)
                            <label wire:key="check-{{ $item->id }}" class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    wire:click="toggleChecklistItem({{ $item->id }})"
                                    @checked($item->is_checked)
                                    class="rounded border-op-border bg-op-surface text-op-accent focus:ring-op-accent"
                                >
                                <span @class(['line-through text-op-secondary' => $item->is_checked])>{{ $item->description }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <form wire:submit="addChecklistItem" class="flex gap-2">
                    <x-input wire:model="newChecklistItem" type="text" placeholder="Novo item..." class="flex-1" />
                    <x-button type="submit" variant="secondary">+</x-button>
                </form>
                <x-input-error :messages="$errors->get('newChecklistItem')" />
            </div>

            {{-- Diagnóstico --}}
            <div class="rounded-xl border border-op-border bg-op-card p-4">
                <h2 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Diagnóstico</h2>

                <form wire:submit="saveDiagnosis" class="space-y-3">
                    <div>
                        <x-label for="diagnosis_category" value="Categoria" />
                        <x-input wire:model="diagnosis_category" id="diagnosis_category" type="text" placeholder="Ex: Rede, Elétrica..." />
                    </div>

                    <div>
                        <x-label for="diagnosis" value="Problema identificado" />
                        <textarea wire:model="diagnosis" id="diagnosis" rows="2" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
                    </div>

                    <div>
                        <x-label for="cause" value="Causa" />
                        <textarea wire:model="cause" id="cause" rows="2" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
                    </div>

                    <div>
                        <x-label for="resolution" value="Solução aplicada" />
                        <textarea wire:model="resolution" id="resolution" rows="2" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
                    </div>

                    <div>
                        <x-label for="recommendation" value="Recomendação" />
                        <textarea wire:model="recommendation" id="recommendation" rows="2" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
                    </div>

                    <div x-data="{ saved: false }" x-on:diagnosis-saved.window="saved = true; setTimeout(() => saved = false, 2000)" class="flex items-center gap-3">
                        <x-button type="submit" variant="secondary">Salvar diagnóstico</x-button>
                        <span x-show="saved" x-transition class="text-xs text-op-success">Salvo.</span>
                    </div>
                </form>
            </div>

            {{-- Evidências --}}
            <div class="rounded-xl border border-op-border bg-op-card p-4">
                <h2 class="mb-3 text-xs font-semibold tracking-wider text-op-secondary uppercase">Evidências</h2>

                @if ($evidenceAttachments->isEmpty())
                    <p class="mb-3 text-xs text-op-secondary">Nenhuma evidência anexada ainda.</p>
                @else
                    <div class="mb-3 space-y-2">
                        @foreach ($evidenceAttachments as $attachment)
                            <div wire:key="evidence-{{ $attachment->id }}" class="flex items-center justify-between rounded-lg border border-op-border p-2 text-xs">
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path) }}" target="_blank" class="truncate hover:text-op-accent-hover">
                                    {{ $attachment->file_name }}
                                </a>
                                <button type="button" wire:click="deleteEvidence({{ $attachment->id }})" wire:confirm="Remover esta evidência?" class="shrink-0 text-op-secondary hover:text-op-danger">
                                    Remover
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <input type="file" wire:model="evidenceFile" accept="image/*,video/*,.pdf" class="block w-full text-xs text-op-secondary file:mr-3 file:rounded-lg file:border-0 file:bg-op-surface file:px-3 file:py-2 file:text-xs file:text-op-primary">
                <x-input-error :messages="$errors->get('evidenceFile')" />

                <div wire:loading wire:target="evidenceFile" class="mt-2 text-xs text-op-secondary">Enviando...</div>

                @if ($evidenceFile)
                    <x-button wire:click="uploadEvidence" variant="secondary" class="mt-2">Confirmar upload</x-button>
                @endif
            </div>

            @if ($workOrder->status === \App\Enums\WorkOrderStatus::InProgress)
                <x-button wire:click="openFinishStage" class="w-full">Finalizar atendimento</x-button>
            @endif
        </div>
    @elseif ($stage === 'finishing')
        <div
            x-data="{
                drawing: false,
                init() {
                    const canvas = this.$refs.signatureCanvas;
                    const ctx = canvas.getContext('2d');
                    ctx.strokeStyle = '#ffffff';
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';

                    const pos = (e) => {
                        const rect = canvas.getBoundingClientRect();
                        const point = e.touches ? e.touches[0] : e;
                        return { x: point.clientX - rect.left, y: point.clientY - rect.top };
                    };

                    const start = (e) => { this.drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
                    const move = (e) => { if (!this.drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); };
                    const end = () => { this.drawing = false; };

                    canvas.addEventListener('mousedown', start);
                    canvas.addEventListener('mousemove', move);
                    canvas.addEventListener('mouseup', end);
                    canvas.addEventListener('mouseleave', end);
                    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); });
                    canvas.addEventListener('touchmove', (e) => { e.preventDefault(); move(e); });
                    canvas.addEventListener('touchend', end);

                    this.clear = () => ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.save = () => $wire.set('signatureDataUrl', canvas.toDataURL('image/png'));
                }
            }"
            class="space-y-4"
        >
            <h2 class="text-sm font-semibold">Confirmação do cliente</h2>
            <p class="text-xs text-op-secondary">"Cliente confirma a execução do serviço?"</p>

            @error('finish')
                <x-alert variant="danger">{{ $message }}</x-alert>
            @enderror

            <div>
                <x-label for="signerName" value="Nome de quem está assinando" />
                <x-input wire:model="signerName" id="signerName" type="text" />
                <x-input-error :messages="$errors->get('signerName')" />
            </div>

            <div>
                <x-label for="signerDocument" value="Documento (opcional)" />
                <x-input wire:model="signerDocument" id="signerDocument" type="text" />
            </div>

            <div>
                <x-label value="Assinatura" />
                <canvas x-ref="signatureCanvas" width="400" height="160" class="w-full touch-none rounded-lg border border-op-border bg-op-surface"></canvas>
                <button type="button" x-on:click="clear()" class="mt-1 text-xs text-op-secondary hover:text-op-primary">Limpar assinatura</button>
                <x-input-error :messages="$errors->get('signatureDataUrl')" />
            </div>

            <div class="flex gap-3">
                <x-button variant="secondary" wire:click="$set('stage', 'working')" class="flex-1">Voltar</x-button>
                <x-button x-on:click="save()" wire:click="submitSignature" class="flex-1">Confirmar e finalizar</x-button>
            </div>
        </div>
    @elseif ($stage === 'rating')
        <div class="space-y-4">
            <h2 class="text-sm font-semibold">Avaliação do cliente</h2>
            <p class="text-xs text-op-secondary">Peça para o cliente avaliar o atendimento antes de sair.</p>

            <div x-data="{ score: @entangle('ratingScore') }" class="flex justify-center gap-2">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" x-on:click="score = {{ $i }}" class="text-3xl" :class="score >= {{ $i }} ? 'text-op-warning' : 'text-op-border'">
                        ★
                    </button>
                @endfor
            </div>

            <div>
                <x-label for="ratingComment" value="Comentário (opcional)" />
                <textarea wire:model="ratingComment" id="ratingComment" rows="3" class="block w-full rounded-lg border border-op-border bg-op-surface px-3 py-2 text-sm focus:border-op-accent focus:outline-none focus:ring-1 focus:ring-op-accent"></textarea>
            </div>

            <div class="flex gap-3">
                <x-button variant="secondary" wire:click="skipRating" class="flex-1">Pular</x-button>
                <x-button wire:click="submitRating" class="flex-1">Enviar avaliação</x-button>
            </div>
        </div>
    @elseif ($stage === 'done')
        <div class="rounded-xl border border-op-border bg-op-card p-6 text-center">
            <p class="text-sm font-medium">Ordem de serviço finalizada.</p>
            <p class="mt-1 text-xs text-op-secondary">Obrigado! O atendimento foi registrado com sucesso.</p>
            <a href="{{ route('portal.index') }}" wire:navigate class="mt-4 inline-block">
                <x-button>Voltar para Minhas Ordens</x-button>
            </a>
        </div>
    @endif
</div>
