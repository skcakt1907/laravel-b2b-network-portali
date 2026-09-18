<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfilDizinTest extends TestCase
{
    use RefreshDatabase;

    private array $uretilenDosyalar = [];

    protected function tearDown(): void
    {
        foreach ($this->uretilenDosyalar as $yol) {
            if (is_file(public_path($yol))) {
                unlink(public_path($yol));
            }
        }

        parent::tearDown();
    }

    private function uye(array $ayar = []): User
    {
        $firma = null;

        if (($ayar['firma'] ?? null) !== null) {
            $firma = Company::create([
                'name' => $ayar['firma'],
                'slug' => \Illuminate\Support\Str::slug($ayar['firma']).'-'.uniqid(),
                'sector' => $ayar['sektor'] ?? null,
                'city' => $ayar['sehir'] ?? null,
            ]);
        }

        $u = new User();
        $u->name = $ayar['ad'] ?? 'Test Uye '.uniqid();
        $u->email = ($ayar['eposta'] ?? 'uye-'.uniqid()).'@dnunity.test';
        $u->password = 'sifre12345';
        $u->title = $ayar['unvan'] ?? null;
        $u->phone = $ayar['telefon'] ?? null;
        $u->company_id = $firma?->id;
        $u->role = $ayar['rol'] ?? User::ROL_UYE;
        $u->status = $ayar['durum'] ?? 'aktif';
        $u->save();

        // user_id bilerek $fillable disinda; acikca atanir
        $p = new Profile();
        $p->user_id = $u->id;
        $p->brand = $ayar['marka'] ?? null;
        $p->services_pitch = $ayar['hizmet'] ?? null;
        $p->is_listed = $ayar['listeli'] ?? true;
        $p->save();

        return $u->fresh();
    }

    // ------------------------------------------------------------ profil duzenleme

    public function test_uye_profilini_guncelleyebilir(): void
    {
        $uye = $this->uye(['firma' => 'Ornek Ajans']);

        $this->actingAs($uye)->post(route('profilim.guncelle'), [
            'name' => 'Guncel Ad',
            'title' => 'Kurucu Ortak',
            'phone' => '0500 111 22 33',
            'brand' => 'tatilimsensin',
            'services_pitch' => 'Web tasarim ve sosyal medya.',
            'seeking_pitch' => 'Turizm sektorunden is birligi.',
            'is_listed' => '1',
        ])->assertRedirect(route('profilim'));

        $uye->refresh();

        $this->assertSame('Guncel Ad', $uye->name);
        $this->assertSame('Kurucu Ortak', $uye->title);
        $this->assertSame('tatilimsensin', $uye->profile->brand);
        $this->assertSame('Web tasarim ve sosyal medya.', $uye->profile->services_pitch);
        $this->assertTrue($uye->profile->is_listed);
    }

    public function test_profil_uzerinden_rol_veya_durum_degistirilemez(): void
    {
        $uye = $this->uye();

        $this->actingAs($uye)->post(route('profilim.guncelle'), [
            'name' => 'Guncel Ad',
            // Yetki yukseltme denemesi
            'role' => 'admin',
            'status' => 'aktif',
            'email' => 'baska@dnunity.test',
        ]);

        $uye->refresh();

        $this->assertSame(User::ROL_UYE, $uye->role);
        $this->assertStringNotContainsString('baska@', $uye->email);
    }

    public function test_profil_fotografi_yuklenir_ve_kirpilir(): void
    {
        $uye = $this->uye();

        $this->actingAs($uye)->post(route('profilim.guncelle'), [
            'name' => $uye->name,
            'avatar' => UploadedFile::fake()->image('ben.jpg', 900, 600),
        ])->assertRedirect(route('profilim'));

        $uye->refresh();

        $this->assertNotNull($uye->avatar_path);
        $this->uretilenDosyalar[] = $uye->avatar_path;
        $this->assertFileExists(public_path($uye->avatar_path));

        [$g, $y] = getimagesize(public_path($uye->avatar_path));
        $this->assertSame(400, $g);
        $this->assertSame(400, $y);
    }

    public function test_instagram_tam_adres_girilse_de_kullanici_adi_saklanir(): void
    {
        $uye = $this->uye();

        $this->actingAs($uye)->post(route('profilim.guncelle'), [
            'name' => $uye->name,
            'instagram' => 'https://www.instagram.com/DnKreatif/',
        ]);

        $this->assertSame('dnkreatif', $uye->fresh()->profile->instagram);
    }

    public function test_uye_firma_bilgilerini_guncelleyebilir(): void
    {
        $uye = $this->uye(['firma' => 'Ornek Ajans']);

        $this->actingAs($uye)->post(route('profilim.guncelle'), [
            'name' => $uye->name,
            'company_sector' => 'Reklam',
            'company_city' => 'Bodrum',
            'company_website' => 'https://ornek.test',
        ]);

        $firma = $uye->fresh()->company;

        $this->assertSame('Reklam', $firma->sector);
        $this->assertSame('Bodrum', $firma->city);
    }

    public function test_misafir_de_kendi_profilini_duzenleyebilir(): void
    {
        $misafir = $this->uye(['rol' => User::ROL_MISAFIR]);
        $misafir->forceFill(['guest_expires_at' => now()->addWeek()])->save();

        $this->actingAs($misafir)->get(route('profilim'))->assertOk();
    }

    // ------------------------------------------------------------ dizin

    public function test_dizin_yalnizca_listelenmeye_acik_aktif_uyeleri_gosterir(): void
    {
        $bakan = $this->uye(['ad' => 'Bakan Uye']);
        $gorunur = $this->uye(['ad' => 'Gorunur Uye']);
        $gizli = $this->uye(['ad' => 'Gizli Uye', 'listeli' => false]);
        $pasif = $this->uye(['ad' => 'Pasif Uye', 'durum' => 'pasif']);

        $this->actingAs($bakan)->get(route('uyeler'))
            ->assertOk()
            ->assertSee('Gorunur Uye')
            ->assertDontSee('Gizli Uye')
            ->assertDontSee('Pasif Uye');
    }

    public function test_dizinde_isme_gore_arama(): void
    {
        $bakan = $this->uye();
        $this->uye(['ad' => 'Ahmet Yilmaz']);
        $this->uye(['ad' => 'Mehmet Demir']);

        $this->actingAs($bakan)->get(route('uyeler', ['q' => 'Ahmet']))
            ->assertOk()
            ->assertSee('Ahmet Yilmaz')
            ->assertDontSee('Mehmet Demir');
    }

    public function test_dizinde_asansor_cumlesine_gore_arama(): void
    {
        $bakan = $this->uye();
        $this->uye(['ad' => 'Web Uzmani', 'hizmet' => 'Web tasarim ve kurumsal kimlik.']);
        $this->uye(['ad' => 'Muhasebeci', 'hizmet' => 'Mali musavirlik hizmetleri.']);

        // Ne is yaptigina gore aramak dizinin asil degeri
        $this->actingAs($bakan)->get(route('uyeler', ['q' => 'kurumsal kimlik']))
            ->assertOk()
            ->assertSee('Web Uzmani')
            ->assertDontSee('Muhasebeci');
    }

    public function test_dizinde_sektor_ve_sehir_filtresi(): void
    {
        $bakan = $this->uye();
        $this->uye(['ad' => 'Bodrumlu Reklamci', 'firma' => 'A Ajans', 'sektor' => 'Reklam', 'sehir' => 'Bodrum']);
        $this->uye(['ad' => 'Izmirli Reklamci', 'firma' => 'B Ajans', 'sektor' => 'Reklam', 'sehir' => 'Izmir']);
        $this->uye(['ad' => 'Bodrumlu Turizmci', 'firma' => 'C Turizm', 'sektor' => 'Turizm', 'sehir' => 'Bodrum']);

        $this->actingAs($bakan)->get(route('uyeler', ['sektor' => 'Reklam', 'sehir' => 'Bodrum']))
            ->assertOk()
            ->assertSee('Bodrumlu Reklamci')
            ->assertDontSee('Izmirli Reklamci')
            ->assertDontSee('Bodrumlu Turizmci');
    }

    public function test_marka_filtresinde_ikisini_secen_uye_de_cikar(): void
    {
        $bakan = $this->uye();
        $this->uye(['ad' => 'Sadece DN', 'marka' => 'dnkreatif']);
        $this->uye(['ad' => 'Her Ikisi', 'marka' => 'ikisi']);
        $this->uye(['ad' => 'Sadece Tatilim', 'marka' => 'tatilimsensin']);

        $this->actingAs($bakan)->get(route('uyeler', ['marka' => 'dnkreatif']))
            ->assertOk()
            ->assertSee('Sadece DN')
            ->assertSee('Her Ikisi')
            ->assertDontSee('Sadece Tatilim');
    }

    public function test_misafir_dizine_giremez(): void
    {
        $misafir = $this->uye(['rol' => User::ROL_MISAFIR]);
        $misafir->forceFill(['guest_expires_at' => now()->addWeek()])->save();

        $this->actingAs($misafir)->get(route('uyeler'))->assertForbidden();
    }

    // ------------------------------------------------------------ profil goruntuleme

    public function test_listelenmeyen_uyenin_profili_acilamaz(): void
    {
        $bakan = $this->uye();
        $gizli = $this->uye(['listeli' => false]);

        $this->actingAs($bakan)->get(route('uyeler.show', $gizli))->assertForbidden();
    }

    public function test_uye_kendi_profilini_gizlese_de_gorebilir(): void
    {
        $gizli = $this->uye(['listeli' => false]);

        $this->actingAs($gizli)->get(route('uyeler.show', $gizli))->assertOk();
    }

    public function test_profil_sayfasi_asansor_cumlesini_gosterir(): void
    {
        $bakan = $this->uye();
        $hedef = $this->uye(['ad' => 'Hedef Uye', 'hizmet' => 'Kurumsal kimlik tasarimi.']);

        $this->actingAs($bakan)->get(route('uyeler.show', $hedef))
            ->assertOk()
            ->assertSee('Hedef Uye')
            ->assertSee('Kurumsal kimlik tasarimi.');
    }
}
