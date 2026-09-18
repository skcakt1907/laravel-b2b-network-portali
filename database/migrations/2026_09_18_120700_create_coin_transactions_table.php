<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tek yonlu islem defteri: bakiye BU TABLODAN HESAPLANIR,
        // hicbir yerde ayri bir bakiye sutunu tutulmaz.
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Kazanim pozitif, harcama negatif
            $table->integer('amount');
            $table->enum('type', ['kazanim', 'harcama', 'duzeltme'])->default('kazanim');
            // Ornek: etkinlik_katilimi, referans, manuel, kupon
            $table->string('reason', 64);
            $table->string('description')->nullable();
            // Islemi doguran kayit (etkinlik, basvuru vb.)
            $table->nullableMorphs('source');
            // Manuel islemlerde islemi yapan admin
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
