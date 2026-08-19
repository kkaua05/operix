<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('photo');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_document')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_attachments');
    }
};
