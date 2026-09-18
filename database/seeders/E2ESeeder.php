<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Playwright testlerinin dayandigi sabit hesaplar.
 *
 * DatabaseSeeder'dan CAGRILMAZ; yalnizca acikca calistirilir:
 *   php artisan db:seed --class=E2ESeeder
 *
 * Her calistirmada ayni duruma getirir (idempotent), boylece testler
 * onceki kosunun biraktigi veriden etkilenmez.
 */
class E2ESeeder extends Seeder
{
    /** Bu hesaplarin tamami test icindir; uretimde bulunmamalidir. */
    public const SIFRE = 'test12345';

    public function run(): void
    {
        $this->hesap('e2e-aktif@dnunity.test', 'E2E Aktif Uye', [
            'role' => User::ROL_UYE,
            'status' => 'aktif',
        ]);

        $this->hesap('e2e-beklemede@dnunity.test', 'E2E Bekleyen Uye', [
            'role' => User::ROL_UYE,
            'status' => 'beklemede',
        ]);

        $this->hesap('e2e-donduruldu@dnunity.test', 'E2E Dondurulmus Uye', [
            'role' => User::ROL_UYE,
            'status' => 'donduruldu',
        ]);

        // Sifre degistirme testi hesabi TUKETIR (sifresini degistirir).
        // Playwright projeleri ayni kosuda sirayla calistigi ve globalSetup
        // koşu basina bir kez dondugu icin her projeye ayri hesap verilir.
        foreach (['chromium', 'mobil'] as $proje) {
            $this->hesap("e2e-gecici-{$proje}@dnunity.test", "E2E Gecici Sifreli Uye ({$proje})", [
                'role' => User::ROL_UYE,
                'status' => 'aktif',
                'must_change_password' => true,
            ]);
        }

        $this->hesap('e2e-misafir-dolmus@dnunity.test', 'E2E Suresi Dolmus Misafir', [
            'role' => User::ROL_MISAFIR,
            'status' => 'aktif',
            'guest_expires_at' => now()->subDay(),
        ]);
    }

    private function hesap(string $eposta, string $ad, array $korumali): void
    {
        $u = User::firstOrNew(['email' => $eposta]);
        $u->name = $ad;
        // Sifre her kosuda sifirlanir; onceki test degistirmis olabilir
        $u->password = Hash::make(self::SIFRE);

        // Korumali alanlar mass-assign edilmez, acikca yazilir
        $u->role = $korumali['role'];
        $u->status = $korumali['status'];
        $u->must_change_password = $korumali['must_change_password'] ?? false;
        $u->guest_expires_at = $korumali['guest_expires_at'] ?? null;
        $u->approved_at = $korumali['status'] === 'aktif' ? now() : null;

        $u->save();
    }
}
