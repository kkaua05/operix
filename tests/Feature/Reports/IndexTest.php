<?php

use App\Livewire\Reports\Index;
use Livewire\Livewire;

test('a user without reports.view is forbidden from the reports page', function () {
    actingAsCompanyUser(['technician']);

    Livewire::test(Index::class)->assertForbidden();
});

test('a user with reports.view can access the reports page and switch tabs', function () {
    actingAsCompanyUser(['manager']);

    Livewire::test(Index::class)
        ->assertOk()
        ->set('activeTab', 'sla')
        ->assertOk()
        ->set('activeTab', 'tecnicos')
        ->assertOk()
        ->set('activeTab', 'financeiro')
        ->assertOk()
        ->set('activeTab', 'estoque')
        ->assertOk();
});

test('exporting the technicians csv streams a download', function () {
    actingAsCompanyUser(['manager']);

    Livewire::test(Index::class)
        ->set('activeTab', 'tecnicos')
        ->call('exportTechniciansCsv')
        ->assertFileDownloaded('relatorio-tecnicos.csv');
});

test('exporting the stock csv streams a download', function () {
    actingAsCompanyUser(['manager']);

    Livewire::test(Index::class)
        ->set('activeTab', 'estoque')
        ->call('exportStockCsv')
        ->assertFileDownloaded('relatorio-estoque-critico.csv');
});
