<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('new');
            $table->string('origin')->default('manual');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->dateTime('sla_due_at')->nullable();
            $table->string('sla_status')->default('normal');
            $table->string('diagnosis_category')->nullable();
            $table->text('diagnosis')->nullable()->comment('Problema identificado');
            $table->text('cause')->nullable();
            $table->text('resolution')->nullable()->comment('Solução aplicada');
            $table->text('recommendation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index(['company_id', 'technician_id']);
            $table->index(['company_id', 'customer_id']);
            $table->index('sla_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
