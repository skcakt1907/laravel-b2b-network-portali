<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'uye', 'misafir'])->default('uye')->after('email');
            // Kapali devre: hesap admin onaylayana kadar 'beklemede' kalir
            $table->enum('status', ['beklemede', 'aktif', 'donduruldu', 'pasif'])
                ->default('beklemede')->after('role');
            $table->foreignId('company_id')->nullable()->after('status')
                ->constrained('companies')->nullOnDelete();
            $table->string('title')->nullable()->after('company_id');
            $table->string('phone', 32)->nullable()->after('title');
            $table->string('avatar_path')->nullable()->after('phone');
            // Gecici sifreyle giren uye ilk oturumda sifresini degistirmek zorunda
            $table->boolean('must_change_password')->default(false)->after('avatar_path');
            $table->timestamp('approved_at')->nullable()->after('must_change_password');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->after('approved_by')
                ->constrained('users')->nullOnDelete();
            // Misafir hesaplarin otomatik pasife dusme zamani
            $table->timestamp('guest_expires_at')->nullable()->after('invited_by');
            $table->timestamp('last_login_at')->nullable()->after('guest_expires_at');

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['invited_by']);
            $table->dropIndex(['role', 'status']);
            $table->dropColumn([
                'role', 'status', 'company_id', 'title', 'phone', 'avatar_path',
                'must_change_password', 'approved_at', 'approved_by', 'invited_by',
                'guest_expires_at', 'last_login_at',
            ]);
        });
    }
};
