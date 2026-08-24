<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ixc_service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // IXC's own OS id — what dedupes a record across sync runs.
            $table->string('external_id');
            $table->string('branch')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('subject')->nullable();
            $table->text('address')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();

            // The scraper's raw captured record for this OS, kept so a
            // field the initial extraction missed can still be recovered
            // without re-running the scraper.
            $table->json('raw_payload')->nullable();

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'external_id']);
            $table->index(['company_id', 'technician_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ixc_service_orders');
    }
};
