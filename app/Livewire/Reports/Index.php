<?php

namespace App\Livewire\Reports;

use App\Services\FinancialService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app', ['title' => 'Relatórios — Operix'])]
class Index extends Component
{
    #[Url(as: 'aba')]
    public string $activeTab = 'operacional';

    #[Url(as: 'de')]
    public string $from = '';

    #[Url(as: 'ate')]
    public string $to = '';

    public function mount(): void
    {
        $this->authorize('reports.view');

        if ($this->from === '') {
            $this->from = now()->startOfMonth()->toDateString();
        }

        if ($this->to === '') {
            $this->to = now()->endOfMonth()->toDateString();
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function range(): array
    {
        return [Carbon::parse($this->from), Carbon::parse($this->to)];
    }

    public function exportTechniciansCsv(ReportService $reportService): StreamedResponse
    {
        $this->authorize('reports.view');

        [$from, $to] = $this->range();
        $rows = $reportService->technicianProductivity((int) auth()->user()->company_id, $from, $to);

        return Response::streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Técnico', 'OS concluídas', 'Tempo médio (h)', 'Avaliação média']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['technician']->name,
                    $row['completed_count'],
                    $row['avg_resolution_hours'],
                    $row['avg_rating'],
                ]);
            }

            fclose($handle);
        }, 'relatorio-tecnicos.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportStockCsv(ReportService $reportService): StreamedResponse
    {
        $this->authorize('reports.view');

        $summary = $reportService->stockSummary((int) auth()->user()->company_id);

        return Response::streamDownload(function () use ($summary) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Produto', 'SKU', 'Estoque atual', 'Estoque mínimo']);

            foreach ($summary['critical_products'] as $product) {
                fputcsv($handle, [$product->name, $product->sku, $product->stock_quantity, $product->min_stock]);
            }

            fclose($handle);
        }, 'relatorio-estoque-critico.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(ReportService $reportService, FinancialService $financialService): View
    {
        [$from, $to] = $this->range();
        $companyId = (int) auth()->user()->company_id;

        return view('livewire.reports.index', [
            'operational' => $this->activeTab === 'operacional' ? $reportService->operationalSummary($companyId, $from, $to) : null,
            'sla' => $this->activeTab === 'sla' ? $reportService->slaSummary($companyId, $from, $to) : null,
            'technicians' => $this->activeTab === 'tecnicos' ? $reportService->technicianProductivity($companyId, $from, $to) : null,
            'financial' => $this->activeTab === 'financeiro' ? $financialService->ledgerTotals($companyId, $from, $to) : null,
            'stock' => $this->activeTab === 'estoque' ? $reportService->stockSummary($companyId) : null,
        ]);
    }
}
