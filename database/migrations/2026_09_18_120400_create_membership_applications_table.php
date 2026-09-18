<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aday henuz kullanici degil; basvuru ayri tabloda tutulur,
        // onaylandiginda users kaydi uretilir.
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->nullable()
                ->constrained('invitations')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('company_name')->nullable();
            $table->string('sector')->nullable();
            $table->string('city')->nullable();
            $table->enum('brand', ['dnkreatif', 'tatilimsensin', 'ikisi'])->nullable();
            // Aday kimin referansiyla geldi
            $table->string('reference_name')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['beklemede', 'onaylandi', 'reddedildi'])
                ->default('beklemede');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('created_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
