<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['online', 'fiziksel'])->default('online');
            // Egitim/akademi icerikleri de ayni takvimden yurur
            $table->enum('category', ['toplanti', 'egitim', 'gezi', 'diger'])
                ->default('toplanti');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->string('online_url')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            // Misafirler yalnizca uye_misafir isaretli kartlari gorebilir
            $table->enum('visibility', ['uye', 'uye_misafir'])->default('uye');
            $table->string('cover_path')->nullable();
            $table->boolean('is_published')->default(false);
            // 3 dakikalik sunum sirasi bu etkinlikte uygulanacak mi
            $table->boolean('has_presentations')->default(false);
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_published', 'starts_at']);
            $table->index(['category', 'starts_at']);
        });

        // invitations.event_id artik hedefini bulabilir
        Schema::table('invitations', function (Blueprint $table) {
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
        });

        Schema::dropIfExists('events');
    }
};
