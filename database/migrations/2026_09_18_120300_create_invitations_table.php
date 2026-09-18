<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            // Davet bagindaki tekil kod
            $table->string('code', 64)->unique();
            $table->enum('type', ['uye', 'misafir'])->default('uye');
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            // Misafir daveti belirli bir etkinlige bagli olabilir.
            // events tablosu bu migrationdan sonra olusuyor; FK sonradan eklenir.
            $table->unsignedBigInteger('event_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
