<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Uyenin hangi marka ile calistigini gosteren rozet
            $table->enum('brand', ['dnkreatif', 'tatilimsensin', 'ikisi'])->nullable();
            // 3 dakikalik asansor cumlesinin iki yarisi
            $table->text('services_pitch')->nullable();
            $table->text('seeking_pitch')->nullable();
            $table->date('birthday')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('website')->nullable();
            $table->string('whatsapp', 32)->nullable();
            // Uye dizininde gorunsun mu
            $table->boolean('is_listed')->default(true);
            $table->timestamps();

            $table->index('brand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
