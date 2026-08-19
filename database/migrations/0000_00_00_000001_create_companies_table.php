<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('document')->nullable()->comment('CNPJ/CPF');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->string('locale')->default('pt_BR');
            $table->string('currency')->default('BRL');
            $table->string('status')->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('document');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
