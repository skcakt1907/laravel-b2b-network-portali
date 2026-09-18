<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Support\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $uye = $request->user();

        return view('profil.duzenle', [
            'uye' => $uye,
            'profil' => $this->profili($uye),
            'firma' => $uye->company,
            'markalar' => Profile::MARKA_ETIKETLERI,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $uye = $request->user();
        $profil = $this->profili($uye);
        $firma = $uye->company;

        $avatarKurali = ImageUploader::avatar();
        $logoKurali = ImageUploader::logo();

        $veri = $request->validate([
            // Kisisel
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'avatar' => $avatarKurali->kurallar(),
            'avatar_sil' => ['nullable', 'boolean'],

            // Profil
            'brand' => ['nullable', Rule::in(array_keys(Profile::MARKA_ETIKETLERI))],
            'services_pitch' => ['nullable', 'string', 'max:600'],
            'seeking_pitch' => ['nullable', 'string', 'max:600'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'linkedin' => ['nullable', 'url', 'max:200'],
            'instagram' => ['nullable', 'string', 'max:200'],
            'website' => ['nullable', 'url', 'max:200'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'is_listed' => ['nullable', 'boolean'],

            // Firma (yalnizca firmasi olan uyeler icin)
            'company_sector' => ['nullable', 'string', 'max:120'],
            'company_city' => ['nullable', 'string', 'max:80'],
            'company_website' => ['nullable', 'url', 'max:200'],
            'company_phone' => ['nullable', 'string', 'max:32'],
            'company_email' => ['nullable', 'email', 'max:180'],
            'company_founded_on' => ['nullable', 'date', 'before:tomorrow'],
            'company_about' => ['nullable', 'string', 'max:1500'],
            'company_logo' => $logoKurali->kurallar(),
        ], [], [
            'name' => 'ad soyad',
            'title' => 'unvan',
            'avatar' => 'profil fotografi',
            'services_pitch' => 'hizmetlerimiz metni',
            'seeking_pitch' => 'arayislarimiz metni',
            'birthday' => 'dogum gunu',
            'company_logo' => 'firma logosu',
            'company_website' => 'firma web adresi',
            'company_email' => 'firma e-postasi',
        ]);

        // --- kisisel bilgiler -------------------------------------------
        $uye->fill([
            'name' => $veri['name'],
            'title' => $veri['title'] ?? null,
            'phone' => $veri['phone'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            $avatarKurali->sil($uye->avatar_path);
            $uye->avatar_path = $avatarKurali->yukle($request->file('avatar'));
        } elseif ($request->boolean('avatar_sil')) {
            $avatarKurali->sil($uye->avatar_path);
            $uye->avatar_path = null;
        }

        $uye->save();

        // --- profil ------------------------------------------------------
        $profil->fill([
            'brand' => $veri['brand'] ?? null,
            'services_pitch' => $veri['services_pitch'] ?? null,
            'seeking_pitch' => $veri['seeking_pitch'] ?? null,
            'birthday' => $veri['birthday'] ?? null,
            'linkedin' => $veri['linkedin'] ?? null,
            'instagram' => $this->instagramKullaniciAdi($veri['instagram'] ?? null),
            'website' => $veri['website'] ?? null,
            'whatsapp' => $veri['whatsapp'] ?? null,
            'is_listed' => $request->boolean('is_listed'),
        ])->save();

        // --- firma -------------------------------------------------------
        if ($firma !== null) {
            $firma->fill([
                'sector' => $veri['company_sector'] ?? null,
                'city' => $veri['company_city'] ?? null,
                'website' => $veri['company_website'] ?? null,
                'phone' => $veri['company_phone'] ?? null,
                'email' => $veri['company_email'] ?? null,
                'founded_on' => $veri['company_founded_on'] ?? null,
                'about' => $veri['company_about'] ?? null,
            ]);

            if ($request->hasFile('company_logo')) {
                $logoKurali->sil($firma->logo_path);
                $firma->logo_path = $logoKurali->yukle($request->file('company_logo'));
            }

            $firma->save();
        }

        return redirect()->route('profilim')->with('basari', 'Profiliniz guncellendi.');
    }

    /** Profil kaydi yoksa olusturur; user_id bilerek $fillable disindadir. */
    private function profili($uye): Profile
    {
        $profil = $uye->profile;

        if ($profil === null) {
            $profil = new Profile();
            $profil->user_id = $uye->id;
            $profil->save();
            $uye->setRelation('profile', $profil);
        }

        return $profil;
    }

    /**
     * Instagram alanina tam adres de yazilabilir; yalnizca kullanici adi saklanir
     * ki gorunumde tek bicimde gosterilebilsin.
     */
    private function instagramKullaniciAdi(?string $deger): ?string
    {
        if ($deger === null || trim($deger) === '') {
            return null;
        }

        $deger = trim($deger);
        $deger = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $deger);

        return Str::lower(trim($deger, "/@ \t\n\r"));
    }
}
