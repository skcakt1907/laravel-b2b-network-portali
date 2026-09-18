<?php

namespace Tests\Feature;

use App\Models\CoinTransaction;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Invitation;
use App\Models\MembershipApplication;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(string $rol = User::ROL_UYE): User
    {
        $u = new User();
        $u->name = 'Test '.$rol.' '.uniqid();
        $u->email = $rol.'-'.uniqid().'@dnunity.test';
        $u->password = 'sifre12345';
        $u->role = $rol;
        $u->status = 'aktif';
        $u->approved_at = now();

        if ($rol === User::ROL_MISAFIR) {
            $u->guest_expires_at = now()->addWeek();
        }

        $u->save();

        return $u;
    }

    private function profil(User $uye, ?string $hizmet = 'Bir hizmet.', ?string $arayis = 'Bir arayis.'): Profile
    {
        $p = new Profile();
        $p->user_id = $uye->id;
        $p->services_pitch = $hizmet;
        $p->seeking_pitch = $arayis;
        $p->save();

        return $p;
    }

    private function basvuru(): MembershipApplication
    {
        $davet = new Invitation();
        $davet->type = 'uye';
        $davet->code = Invitation::yeniKod();
        $davet->invited_by = $this->kullanici(User::ROL_ADMIN)->id;
        $davet->save();

        return MembershipApplication::create([
            'invitation_id' => $davet->id,
            'name' => 'Bekleyen Aday '.uniqid(),
            'email' => 'aday-'.uniqid().'@firma.test',
            'company_name' => 'Aday Firma',
        ]);
    }

    private function etkinlik(array $ayar = []): Event
    {
        return Event::create([
            'title' => $ayar['baslik'] ?? 'Toplanti '.uniqid(),
            'slug' => 'etkinlik-'.uniqid(),
            'type' => 'online',
            'category' => 'toplanti',
            'starts_at' => $ayar['baslangic'] ?? now()->addWeek(),
            'visibility' => $ayar['gorunurluk'] ?? 'uye',
            'is_published' => $ayar['yayinda'] ?? true,
        ]);
    }

    // ------------------------------------------------------------ yonetim paneli

    public function test_yonetim_paneli_bekleyen_basvuruyu_gosterir(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $basvuru = $this->basvuru();

        $this->actingAs($admin)->get(route('yonetim.index'))
            ->assertOk()
            ->assertSee($basvuru->name)
            ->assertSee('incelenmeyi bekliyor');
    }

    public function test_yonetim_paneli_yaklasan_etkinligi_gosterir(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $this->etkinlik(['baslik' => 'Yaklasan Toplantimiz']);
        $this->etkinlik(['baslik' => 'Gecmis Toplantimiz', 'baslangic' => now()->subWeek()]);

        $this->actingAs($admin)->get(route('yonetim.index'))
            ->assertOk()
            ->assertSee('Yaklasan Toplantimiz')
            ->assertDontSee('Gecmis Toplantimiz');
    }

    public function test_yonetim_paneli_bu_ayki_dogum_gunlerini_listeler(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        $buAy = $this->kullanici();
        $buAy->forceFill(['name' => 'Bu Ay Dogan'])->save();
        $p1 = $this->profil($buAy);
        $p1->forceFill(['birthday' => now()->subYears(40)->startOfMonth()->addDays(5)])->save();

        // Misafir secildi: "son katilanlar" listesi yalnizca uye/admin gosterir,
        // boylece bu isim sayfada SADECE dogum gunu listesinden gelebilir.
        $baskaAy = $this->kullanici(User::ROL_MISAFIR);
        $baskaAy->forceFill(['name' => 'Baska Ay Dogan'])->save();
        $p2 = $this->profil($baskaAy);
        $p2->forceFill(['birthday' => now()->subYears(40)->addMonths(3)])->save();

        $this->actingAs($admin)->get(route('yonetim.index'))
            ->assertOk()
            ->assertSee('Bu Ay Dogan')
            ->assertDontSee('Baska Ay Dogan');
    }

    public function test_yonetim_paneli_kurulus_yildonumunu_listeler(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        Company::create([
            'name' => 'Yildonumu Firmasi',
            'slug' => 'yildonumu-firmasi',
            'founded_on' => now()->subYears(10)->startOfMonth()->addDays(3),
        ]);

        $this->actingAs($admin)->get(route('yonetim.index'))
            ->assertOk()
            ->assertSee('Yildonumu Firmasi')
            ->assertSee('kurulusunun 10. yili');
    }

    public function test_yonetim_paneli_asansor_cumlesi_eksik_uyeleri_sayar(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        $dolu = $this->kullanici();
        $this->profil($dolu);

        $bos = $this->kullanici();
        $this->profil($bos, hizmet: null);

        $this->actingAs($admin)->get(route('yonetim.index'))
            ->assertOk()
            ->assertSee('asansor cumlesini');
    }

    public function test_yonetim_paneli_dolasimdaki_coini_gosterir(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $uye = $this->kullanici();

        foreach ([100, -30] as $miktar) {
            $t = new CoinTransaction();
            $t->user_id = $uye->id;
            $t->amount = $miktar;
            $t->type = $miktar > 0 ? 'kazanim' : 'harcama';
            $t->reason = 'test';
            $t->save();
        }

        // Dolasimdaki 70, dagitilan 100 olmali
        $this->actingAs($admin)->get(route('yonetim.index'))
            ->assertOk()
            ->assertSee('70')
            ->assertSee('toplam 100 dagitildi');
    }

    public function test_uye_yonetim_paneline_giremez(): void
    {
        $this->actingAs($this->kullanici())->get(route('yonetim.index'))->assertForbidden();
    }

    // ------------------------------------------------------------ uye paneli

    public function test_uye_paneli_yaklasan_etkinlikleri_gosterir(): void
    {
        $uye = $this->kullanici();
        $this->profil($uye);
        $this->etkinlik(['baslik' => 'Panelde Gorunecek Toplanti']);

        $this->actingAs($uye)->get(route('panel'))
            ->assertOk()
            ->assertSee('Panelde Gorunecek Toplanti')
            ->assertSee('Yanit verin');
    }

    public function test_uye_paneli_asansor_cumlesi_eksikse_uyarir(): void
    {
        $uye = $this->kullanici();
        $this->profil($uye, hizmet: null);

        $this->actingAs($uye)->get(route('panel'))
            ->assertOk()
            ->assertSee('Asansor cumleniz eksik');
    }

    public function test_profili_dolu_uyeye_uyari_gosterilmez(): void
    {
        $uye = $this->kullanici();
        $this->profil($uye);

        $this->actingAs($uye)->get(route('panel'))
            ->assertOk()
            ->assertDontSee('Asansor cumleniz eksik');
    }

    public function test_uye_panelinde_katilim_yaniti_dogru_gosterilir(): void
    {
        $uye = $this->kullanici();
        $this->profil($uye);
        $e = $this->etkinlik();

        EventAttendee::create([
            'event_id' => $e->id,
            'user_id' => $uye->id,
            'rsvp' => 'katiliyor',
            'responded_at' => now(),
        ]);

        $this->actingAs($uye)->get(route('panel'))
            ->assertOk()
            ->assertSee('Katiliyorsunuz')
            ->assertDontSee('Yanit verin');
    }

    public function test_misafir_panelinde_coin_ve_dizin_kisayolu_yok(): void
    {
        $misafir = $this->kullanici(User::ROL_MISAFIR);

        $this->actingAs($misafir)->get(route('panel'))
            ->assertOk()
            ->assertDontSee('Unitycoin bakiyeniz')
            ->assertDontSee('Uye dizininde ara');
    }

    public function test_misafir_panelinde_yalnizca_kendisine_acik_etkinlik_gorunur(): void
    {
        $misafir = $this->kullanici(User::ROL_MISAFIR);
        $this->etkinlik(['baslik' => 'Uyelere Ozel Toplanti', 'gorunurluk' => 'uye']);
        $this->etkinlik(['baslik' => 'Misafire Acik Toplanti', 'gorunurluk' => 'uye_misafir']);

        $this->actingAs($misafir)->get(route('panel'))
            ->assertOk()
            ->assertSee('Misafire Acik Toplanti')
            ->assertDontSee('Uyelere Ozel Toplanti');
    }
}
