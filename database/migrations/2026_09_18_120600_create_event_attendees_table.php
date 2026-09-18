<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('rsvp', ['yanitsiz', 'katiliyor', 'katilmiyor'])
                ->default('yanitsiz');
            // Katilim onayina gore uretilen 3 dakikalik sunum sirasi
            $table->unsignedSmallInteger('presentation_order')->nullable();
            // Toplanti sonrasi yoklama; coin kazanimi buna baglanir
            $table->boolean('attended')->default(false);
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Bir uye bir etkinlige tek kayit birakabilir
            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'rsvp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
    }
};
