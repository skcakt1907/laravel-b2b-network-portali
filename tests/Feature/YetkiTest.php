<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YetkiTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(string $rol, array $ek = []): User
    {
        $u = new User();
        $u->name = 'Test '.$rol;
        $u->email = $rol.'-'.uniqid().'@dnunity.test';
        $u->password = 'sifre12345';
        $u->role = $rol;
        $u->status = $ek['status'] ?? 'aktif';
        $u->guest_expires_at = $ek['guest_expires_at'] ?? null;
        $u->save();

        return $u;
    }

    /**
     * Profil olusturur. user_id bilerek $fillable disinda oldugu icin
     * acikca atanir; Profile::create(['user_id' => ...]) onu dusurur.
     * (Seeder'lar Model::unguarded() icinde calistigi icin orada sorun cikmaz.)
     */
    private function profil(User $sahip, bool $listelensin): Profile
    {
        $p = new Profile();
        $p->user_id = $sahip->id;
        $p->is_listed = $listelensin;
        $p->save();

        return $p;
    }

    private function etkinlik(array $ozellikler = []): Event
    {
        return Event::create([
            'title' => 'Test Etkinlik',
            'slug' => 'test-etkinlik-'.uniqid(),
            'type' => 'online',
            'category' => 'toplanti',
            'starts_at' => $ozellikler['starts_at'] ?? now()->addWeek(),
            'visibility' => $ozellikler['visibility'] ?? 'uye',
            'is_published' => $ozellikler['is_published'] ?? true,
        ]);
    }

    // ------------------------------------------------------- yonetim alani

    public function test_admin_yonetim_alanina_girer(): void
    {
        $this->actingAs($this->kullanici(User::ROL_ADMIN))
            ->get('/yonetim/basvurular')
            ->assertOk();
    }

    public function test_uye_yonetim_alanina_giremez(): void
    {
        $this->actingAs($this->kullanici(User::ROL_UYE))
            ->get('/yonetim/basvurular')
            ->assertForbidden();
    }

    public function test_misafir_yonetim_alanina_giremez(): void
    {
        $this->actingAs($this->kullanici(User::ROL_MISAFIR))
            ->get('/yonetim/coin')
            ->assertForbidden();
    }

    public function test_tum_yonetim_adresleri_uyeye_kapali(): void
    {
        $uye = $this->kullanici(User::ROL_UYE);

        foreach ([
            '/yonetim',
            '/yonetim/basvurular',
            '/yonetim/uyeler',
            '/yonetim/etkinlikler',
            '/yonetim/coin',
        ] as $adres) {
            $this->actingAs($uye)->get($adres)
                ->assertForbidden("{$adres} uyeye acik kalmis");
        }
    }

    // ------------------------------------------------------- uyeye ozel alanlar

    public function test_misafir_uye_dizinini_goremez(): void
    {
        $this->actingAs($this->kullanici(User::ROL_MISAFIR))
            ->get('/uyeler')
            ->assertForbidden();
    }

    public function test_misafir_cuzdani_goremez(): void
    {
        $this->actingAs($this->kullanici(User::ROL_MISAFIR))
            ->get('/cuzdan')
            ->assertForbidden();
    }

    public function test_uye_dizini_ve_cuzdani_gorur(): void
    {
        $uye = $this->kullanici(User::ROL_UYE);

        $this->actingAs($uye)->get('/uyeler')->assertOk();
        $this->actingAs($uye)->get('/cuzdan')->assertOk();
    }

    public function test_misafir_etkinlikleri_ve_profilini_gorur(): void
    {
        $misafir = $this->kullanici(User::ROL_MISAFIR);

        $this->actingAs($misafir)->get('/etkinlikler')->assertOk();
        $this->actingAs($misafir)->get('/profilim')->assertOk();
    }

    // ------------------------------------------------------- EventPolicy

    public function test_misafir_yalnizca_kendisine_acilan_etkinligi_gorur(): void
    {
        $misafir = $this->kullanici(User::ROL_MISAFIR);

        $this->assertFalse($misafir->can('view', $this->etkinlik(['visibility' => 'uye'])));
        $this->assertTrue($misafir->can('view', $this->etkinlik(['visibility' => 'uye_misafir'])));
    }

    public function test_yayinlanmamis_etkinligi_yalnizca_admin_gorur(): void
    {
        $taslak = $this->etkinlik(['is_published' => false]);

        $this->assertFalse($this->kullanici(User::ROL_UYE)->can('view', $taslak));
        $this->assertTrue($this->kullanici(User::ROL_ADMIN)->can('view', $taslak));
    }

    public function test_gecmis_etkinlige_katilim_onayi_verilemez(): void
    {
        $gecmis = $this->etkinlik(['starts_at' => now()->subDay()]);
        $gelecek = $this->etkinlik(['starts_at' => now()->addDay()]);
        $uye = $this->kullanici(User::ROL_UYE);

        $this->assertFalse($uye->can('rsvp', $gecmis));
        $this->assertTrue($uye->can('rsvp', $gelecek));
    }

    public function test_etkinlik_olusturmak_adminin_isidir(): void
    {
        $this->assertTrue($this->kullanici(User::ROL_ADMIN)->can('create', Event::class));
        $this->assertFalse($this->kullanici(User::ROL_UYE)->can('create', Event::class));
    }

    // ------------------------------------------------------- UserPolicy

    public function test_misafir_uye_listesini_goremez(): void
    {
        $this->assertFalse($this->kullanici(User::ROL_MISAFIR)->can('viewAny', User::class));
        $this->assertTrue($this->kullanici(User::ROL_UYE)->can('viewAny', User::class));
    }

    public function test_dizinde_gorunmek_istemeyen_uye_listelenmez(): void
    {
        $bakan = $this->kullanici(User::ROL_UYE);

        $gizli = $this->kullanici(User::ROL_UYE);
        $this->profil($gizli, listelensin: false);

        $acik = $this->kullanici(User::ROL_UYE);
        $this->profil($acik, listelensin: true);

        $this->assertFalse($bakan->can('view', $gizli));
        $this->assertTrue($bakan->can('view', $acik));

        // Gizlense de kendi profilini ve admin herkesi gorur
        $this->assertTrue($gizli->can('view', $gizli));
        $this->assertTrue($this->kullanici(User::ROL_ADMIN)->can('view', $gizli));
    }

    public function test_onaysiz_hesap_dizinde_gorunmez(): void
    {
        $bakan = $this->kullanici(User::ROL_UYE);
        $bekleyen = $this->kullanici(User::ROL_UYE, ['status' => 'beklemede']);
        $this->profil($bekleyen, listelensin: true);

        $this->assertFalse($bakan->can('view', $bekleyen));
    }

    public function test_uye_baskasinin_profilini_duzenleyemez(): void
    {
        $uye = $this->kullanici(User::ROL_UYE);
        $baskasi = $this->kullanici(User::ROL_UYE);

        $this->assertTrue($uye->can('update', $uye));
        $this->assertFalse($uye->can('update', $baskasi));
        $this->assertTrue($this->kullanici(User::ROL_ADMIN)->can('update', $baskasi));
    }

    public function test_admin_kendi_hesabini_yonetemez(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $baskasi = $this->kullanici(User::ROL_UYE);

        // Yanlislikla kendi erisimini kapatmasini onler
        $this->assertFalse($admin->can('manage', $admin));
        $this->assertTrue($admin->can('manage', $baskasi));
    }

    public function test_misafir_misafir_davet_edemez(): void
    {
        $this->assertFalse($this->kullanici(User::ROL_MISAFIR)->can('inviteGuest', User::class));
        $this->assertTrue($this->kullanici(User::ROL_UYE)->can('inviteGuest', User::class));
    }
}
