<?php

namespace App\Services;

use App\Models\User;
use App\Support\ImageUploader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Uyelik durumu degisiklikleri ve KVKK kapsaminda veri silme.
 */
class MembershipTermination
{
    /** Gecici uzaklastirma: hesap durur, veri korunur, geri alinabilir. */
    public function dondur(User $uye): void
    {
        $this->kendineKarsiKoru($uye);
        $uye->forceFill(['status' => 'donduruldu'])->save();
    }

    /** Kalici kapatma: hesap kapanir ama veri durur (kayit izi icin). */
    public function pasifeCek(User $uye): void
    {
        $this->kendineKarsiKoru($uye);
        $uye->forceFill(['status' => 'pasif'])->save();
    }

    public function aktiflestir(User $uye): void
    {
        $uye->forceFill([
            'status' => 'aktif',
            'approved_at' => $uye->approved_at ?? now(),
        ])->save();
    }

    /**
     * KVKK "unutulma hakki": kisisel veriler geri donusu olmayacak sekilde silinir.
     *
     * Kayit TAMAMEN silinmez, anonimlestirilir. Sebebi: Unitycoin islem defteri
     * ve etkinlik katilim kayitlari bu kullaniciya bagli; satir silinirse
     * gecmis bakiye ve katilim istatistikleri de yok olur. Anonimlestirme
     * kisiyi tanimlanamaz hale getirirken toplulugun gecmisini bozmaz.
     */
    public function anonimlestir(User $uye): void
    {
        $this->kendineKarsiKoru($uye);

        DB::transaction(function () use ($uye) {
            // Profil fotografi diskten silinir
            if ($uye->avatar_path) {
                ImageUploader::avatar()->sil($uye->avatar_path);
            }

            $uye->profile?->forceFill([
                'brand' => null,
                'services_pitch' => null,
                'seeking_pitch' => null,
                'birthday' => null,
                'linkedin' => null,
                'instagram' => null,
                'website' => null,
                'whatsapp' => null,
                'is_listed' => false,
            ])->save();

            $uye->forceFill([
                'name' => 'Silinmis Uye',
                // E-posta benzersiz olmali; geri donusu olmayan bir deger yazilir
                'email' => 'silinmis-'.$uye->id.'@dnunity.invalid',
                'email_verified_at' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'phone' => null,
                'title' => null,
                'avatar_path' => null,
                'company_id' => null,
                'status' => 'pasif',
                'must_change_password' => false,
            ])->save();
        });
    }

    /**
     * Admin kendi hesabini donduramaz/silemez.
     * Yanlislikla tek yoneticinin sistemden kilitlenmesini onler.
     */
    private function kendineKarsiKoru(User $uye): void
    {
        if (auth()->check() && auth()->user()->is($uye)) {
            throw new RuntimeException('Kendi hesabiniz uzerinde bu islemi yapamazsiniz.');
        }
    }
}
