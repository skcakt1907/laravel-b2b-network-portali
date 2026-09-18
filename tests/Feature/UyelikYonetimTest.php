<?php

namespace Tests\Feature;

use App\Models\CoinTransaction;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UyelikYonetimTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(string $rol = User::ROL_UYE, string $durum = 'aktif'): User
    {
        $u = new User();
        $u->name = 'Test '.$rol.' '.uniqid();
        $u->email = $rol.'-'.uniqid().'@dnunity.test';
        $u->password = 'sifre12345';
        $u->role = $rol;
        $u->status = $durum;
        $u->save();

        return $u;
    }

    // ------------------------------------------------------- durum degisiklikleri

    public function test_admin_uyeyi_dondurabilir(): void
    {
        $uye = $this->kullanici();

        $this->actingAs($this->kullanici(User::ROL_ADMIN))
            ->post(route('yonetim.uyeler.durum', $uye), ['islem' => 'dondur'])
            ->assertRedirect();

        $this->assertSame('donduruldu', $uye->fresh()->status);
    }

    public function test_dondurulan_uye_giris_yapamaz(): void
    {
        $uye = $this->kullanici();
        $uye->forceFill(['status' => 'donduruldu'])->save();

        $this->post('/giris', ['email' => $uye->email, 'password' => 'sifre12345'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Asil risk bu: giriste denetim yapmak yetmez, dondurma anindan once
     * acilmis bir oturum devam ediyor olabilir.
     */
    public function test_acik_oturumu_olan_uye_dondurulunca_disari_atilir(): void
    {
        $uye = $this->kullanici();

        $this->actingAs($uye)->get('/panel')->assertOk();

        $uye->forceFill(['status' => 'donduruldu'])->save();

        $this->get('/panel')->assertRedirect(route('giris'));
        $this->assertGuest();
    }

    public function test_suresi_dolan_misafir_acik_oturumda_kalamaz(): void
    {
        $misafir = $this->kullanici(User::ROL_MISAFIR);
        $misafir->forceFill(['guest_expires_at' => now()->addHour()])->save();

        $this->actingAs($misafir)->get('/panel')->assertOk();

        $misafir->forceFill(['guest_expires_at' => now()->subMinute()])->save();

        $this->get('/panel')->assertRedirect(route('giris'));
        $this->assertGuest();
    }

    public function test_dondurulan_uye_yeniden_aktiflestirilebilir(): void
    {
        $uye = $this->kullanici(User::ROL_UYE, 'donduruldu');
        $admin = $this->kullanici(User::ROL_ADMIN);

        $this->actingAs($admin)
            ->post(route('yonetim.uyeler.durum', $uye), ['islem' => 'aktiflestir']);

        $uye->refresh();
        $this->assertSame('aktif', $uye->status);
        $this->assertNotNull($uye->approved_at);
    }

    public function test_admin_kendi_hesabini_donduramaz(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        $this->actingAs($admin)
            ->post(route('yonetim.uyeler.durum', $admin), ['islem' => 'dondur'])
            ->assertSessionHas('hata');

        $this->assertSame('aktif', $admin->fresh()->status);
    }

    // ------------------------------------------------------- KVKK silme

    public function test_anonimlestirme_kisisel_verileri_siler(): void
    {
        $uye = $this->kullanici();
        $uye->forceFill(['phone' => '0500 000 00 00', 'title' => 'Kurucu'])->save();

        // user_id bilerek $fillable disinda; acikca atanir
        $profil = new Profile();
        $profil->user_id = $uye->id;
        $profil->services_pitch = 'Gizli metin';
        $profil->linkedin = 'https://linkedin.com/in/ornek';
        $profil->is_listed = true;
        $profil->save();

        $eskiAd = $uye->name;

        $this->actingAs($this->kullanici(User::ROL_ADMIN))
            ->post(route('yonetim.uyeler.anonimlestir', $uye), ['onay_adi' => $eskiAd])
            ->assertRedirect(route('yonetim.uyeler.index'));

        $uye->refresh();

        $this->assertSame('Silinmis Uye', $uye->name);
        $this->assertStringEndsWith('@dnunity.invalid', $uye->email);
        $this->assertNull($uye->phone);
        $this->assertNull($uye->title);
        $this->assertSame('pasif', $uye->status);

        $profil = $uye->profile()->first();
        $this->assertNull($profil->services_pitch);
        $this->assertNull($profil->linkedin);
        $this->assertFalse($profil->is_listed);
    }

    public function test_anonimlestirme_coin_gecmisini_korur(): void
    {
        $uye = $this->kullanici();

        $islem = new CoinTransaction();
        $islem->user_id = $uye->id;
        $islem->amount = 25;
        $islem->type = 'kazanim';
        $islem->reason = 'etkinlik_katilimi';
        $islem->save();

        $this->actingAs($this->kullanici(User::ROL_ADMIN))
            ->post(route('yonetim.uyeler.anonimlestir', $uye), ['onay_adi' => $uye->name]);

        // Kisi tanimlanamaz hale geldi ama defter bozulmadi
        $this->assertSame(1, CoinTransaction::where('user_id', $uye->id)->count());
        $this->assertSame(25, $uye->fresh()->coinBalance());
    }

    public function test_yanlis_onay_metni_silmeyi_engeller(): void
    {
        $uye = $this->kullanici();
        $eskiAd = $uye->name;

        $this->actingAs($this->kullanici(User::ROL_ADMIN))
            ->post(route('yonetim.uyeler.anonimlestir', $uye), ['onay_adi' => 'yanlis isim'])
            ->assertSessionHas('hata');

        $this->assertSame($eskiAd, $uye->fresh()->name);
    }

    public function test_admin_kendi_verisini_silemez(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        $this->actingAs($admin)
            ->post(route('yonetim.uyeler.anonimlestir', $admin), ['onay_adi' => $admin->name])
            ->assertSessionHas('hata');

        $this->assertSame('aktif', $admin->fresh()->status);
    }

    // ------------------------------------------------------- yetki

    public function test_uye_yonetim_ekranlarina_erisemez(): void
    {
        $uye = $this->kullanici();
        $hedef = $this->kullanici();

        $this->actingAs($uye)->get(route('yonetim.uyeler.index'))->assertForbidden();
        $this->actingAs($uye)->get(route('yonetim.uyeler.show', $hedef))->assertForbidden();
        $this->actingAs($uye)
            ->post(route('yonetim.uyeler.durum', $hedef), ['islem' => 'dondur'])
            ->assertForbidden();

        $this->assertSame('aktif', $hedef->fresh()->status);
    }

    // ------------------------------------------------------- listeleme

    public function test_liste_duruma_gore_suzulur(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $aktif = $this->kullanici(User::ROL_UYE, 'aktif');
        $donduruldu = $this->kullanici(User::ROL_UYE, 'donduruldu');

        $this->actingAs($admin)
            ->get(route('yonetim.uyeler.index', ['durum' => 'donduruldu']))
            ->assertOk()
            ->assertSee($donduruldu->email)
            ->assertDontSee($aktif->email);
    }

    public function test_liste_ada_gore_aranabilir(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        $aranan = $this->kullanici();
        $aranan->forceFill(['name' => 'Bulunacak Kisi'])->save();
        $digeri = $this->kullanici();
        $digeri->forceFill(['name' => 'Baska Biri'])->save();

        $this->actingAs($admin)
            ->get(route('yonetim.uyeler.index', ['q' => 'Bulunacak']))
            ->assertOk()
            ->assertSee('Bulunacak Kisi')
            ->assertDontSee('Baska Biri');
    }
}
