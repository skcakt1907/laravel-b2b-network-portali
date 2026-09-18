<?php

namespace App\Services;

use App\Mail\HosGeldinMail;
use App\Models\Company;
use App\Models\MembershipApplication;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Basvuru onaylama / reddetme.
 *
 * Onay tek bir islemde olur: kullanici, firma ve profil birlikte olusur,
 * daveti kullanilmis isaretlenir. Herhangi biri patlarsa hicbiri kalmaz —
 * yarim kalmis, giris yapamayan hesap olusmaz.
 */
class MembershipApproval
{
    /**
     * Basvuruyu onaylar, uyeyi olusturur ve hos geldin e-postasini gonderir.
     *
     * @return array{user: User, gecici_sifre: string}
     */
    public function onayla(MembershipApplication $basvuru, User $onaylayan): array
    {
        if (! $basvuru->isBeklemede()) {
            throw new RuntimeException('Bu basvuru zaten sonuclandirilmis.');
        }

        if (User::where('email', $basvuru->email)->exists()) {
            throw new RuntimeException('Bu e-posta adresiyle zaten bir uye kayitli.');
        }

        // Gecici sifre yalnizca burada uretilir ve saklanmaz; e-postayla gider
        $geciciSifre = Str::password(12, symbols: false);

        $uye = DB::transaction(function () use ($basvuru, $onaylayan, $geciciSifre) {
            $firma = $this->firmayiBulVeyaOlustur($basvuru);

            $uye = new User();
            $uye->name = $basvuru->name;
            $uye->email = $basvuru->email;
            $uye->password = Hash::make($geciciSifre);
            $uye->phone = $basvuru->phone;
            $uye->company_id = $firma?->id;

            // Korumali alanlar acikca atanir
            $uye->role = User::ROL_UYE;
            $uye->status = 'aktif';
            $uye->must_change_password = true;
            $uye->approved_at = now();
            $uye->approved_by = $onaylayan->id;
            $uye->invited_by = $basvuru->invitation?->invited_by;
            $uye->save();

            $profil = new Profile();
            $profil->user_id = $uye->id;
            $profil->brand = $basvuru->brand;
            $profil->save();

            $basvuru->forceFill([
                'status' => 'onaylandi',
                'reviewed_by' => $onaylayan->id,
                'reviewed_at' => now(),
                'created_user_id' => $uye->id,
            ])->save();

            $basvuru->invitation?->forceFill([
                'used_at' => now(),
                'created_user_id' => $uye->id,
            ])->save();

            return $uye;
        });

        // E-posta gonderimi akisi bozmamali: onay zaten veritabanina yazildi
        try {
            Mail::to($uye->email)->send(new HosGeldinMail($uye, $geciciSifre));
        } catch (\Throwable $e) {
            Log::error('Hos geldin e-postasi gonderilemedi', [
                'user_id' => $uye->id,
                'hata' => $e->getMessage(),
            ]);
        }

        return ['user' => $uye, 'gecici_sifre' => $geciciSifre];
    }

    public function reddet(MembershipApplication $basvuru, User $reddeden, ?string $not = null): void
    {
        if (! $basvuru->isBeklemede()) {
            throw new RuntimeException('Bu basvuru zaten sonuclandirilmis.');
        }

        $basvuru->forceFill([
            'status' => 'reddedildi',
            'reviewed_by' => $reddeden->id,
            'reviewed_at' => now(),
            'admin_note' => $not,
        ])->save();
    }

    /**
     * Basvurudaki firma adina gore mevcut firmayi bulur, yoksa olusturur.
     *
     * Eslesme slug uzerinden yapilir; "ABC Reklam" ile "abc reklam" ayni
     * firmadir. Yanlis eslesme ihtimaline karsi admin, onaydan sonra uyenin
     * firmasini uye yonetiminden degistirebilir.
     */
    private function firmayiBulVeyaOlustur(MembershipApplication $basvuru): ?Company
    {
        $ad = trim((string) $basvuru->company_name);

        if ($ad === '') {
            return null;
        }

        $slug = Str::slug($ad);

        if ($slug === '') {
            return null;
        }

        $firma = Company::where('slug', $slug)->first();

        if ($firma !== null) {
            return $firma;
        }

        return Company::create([
            'name' => $ad,
            'slug' => $slug,
            'sector' => $basvuru->sector,
            'city' => $basvuru->city,
        ]);
    }
}
