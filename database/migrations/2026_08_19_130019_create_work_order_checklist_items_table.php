<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_checklist_id')->constrained('work_order_checklists')->cascadeOnDelete();
            $table->string('description');
            $table->boolean('is_checked')->default(false);
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('checked_at')->nullable();
            $table->timestamps();

            $table->index('work_order_checklist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_checklist_items');
    }
};
