<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->dateTime('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_events');
    }
};
