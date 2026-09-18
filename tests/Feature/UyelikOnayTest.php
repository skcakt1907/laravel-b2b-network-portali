<?php

namespace Tests\Feature;

use App\Mail\HosGeldinMail;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UyelikOnayTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = new User();
        $u->name = 'Yonetici';
        $u->email = 'admin-'.uniqid().'@dnunity.test';
        $u->password = 'sifre12345';
        $u->role = User::ROL_ADMIN;
        $u->status = 'aktif';
        $u->save();

        return $u;
    }

    private function davet(array $ozellikler = []): Invitation
    {
        $d = new Invitation();
        $d->type = 'uye';
        $d->name = $ozellikler['name'] ?? null;
        $d->email = $ozellikler['email'] ?? null;
        $d->expires_at = $ozellikler['expires_at'] ?? now()->addWeek();
        $d->code = Invitation::yeniKod();
        $d->invited_by = ($ozellikler['inviter'] ?? $this->admin())->id;

        if (array_key_exists('used_at', $ozellikler)) {
            $d->used_at = $ozellikler['used_at'];
        }

        $d->save();

        return $d;
    }

    private function basvuruVerisi(array $ek = []): array
    {
        return array_merge([
            'name' => 'Aday Kisi',
            'email' => 'aday@firma.test',
            'phone' => '0500 111 22 33',
            'company_name' => 'Aday Reklam Ajansi',
            'sector' => 'Reklam',
            'city' => 'Mugla',
            'brand' => 'dnkreatif',
            'reference_name' => 'Mevcut Uye',
            'message' => 'Merhaba, katilmak istiyorum.',
        ], $ek);
    }

    // ------------------------------------------------------------ davet kapisi

    public function test_davet_kodu_olmadan_basvuru_formu_acilmaz(): void
    {
        $this->get('/davet/olmayan-kod')->assertNotFound();
    }

    public function test_gecerli_davet_formu_acar(): void
    {
        $davet = $this->davet(['name' => 'Aday Kisi', 'email' => 'aday@firma.test']);

        $this->get('/davet/'.$davet->code)
            ->assertOk()
            // Davetteki bilgiler forma on dolgu gelir
            ->assertSee('Aday Kisi')
            ->assertSee('aday@firma.test');
    }

    public function test_suresi_dolmus_davet_reddedilir(): void
    {
        $davet = $this->davet(['expires_at' => now()->subDay()]);

        $this->get('/davet/'.$davet->code)->assertNotFound();
        $this->post('/davet/'.$davet->code, $this->basvuruVerisi())->assertNotFound();
    }

    public function test_kullanilmis_davet_tekrar_kullanilamaz(): void
    {
        $davet = $this->davet(['used_at' => now()]);

        $this->get('/davet/'.$davet->code)->assertNotFound();
    }

    // ------------------------------------------------------------ basvuru

    public function test_basvuru_beklemede_olarak_kaydedilir(): void
    {
        $davet = $this->davet();

        $this->post('/davet/'.$davet->code, $this->basvuruVerisi())
            ->assertRedirect(route('davet.tesekkur'));

        $basvuru = MembershipApplication::first();

        $this->assertNotNull($basvuru);
        $this->assertSame('Aday Kisi', $basvuru->name);
        $this->assertSame('beklemede', $basvuru->status);
        $this->assertSame($davet->id, $basvuru->invitation_id);
    }

    public function test_basvuru_kendini_onayli_olarak_kaydettiremez(): void
    {
        $davet = $this->davet();

        // Kotu niyetli form: status ve reviewed_by gondermeye calisiyor
        $this->post('/davet/'.$davet->code, $this->basvuruVerisi([
            'status' => 'onaylandi',
            'reviewed_by' => 1,
            'created_user_id' => 1,
        ]));

        $basvuru = MembershipApplication::first();

        $this->assertSame('beklemede', $basvuru->status);
        $this->assertNull($basvuru->reviewed_by);
        $this->assertNull($basvuru->created_user_id);
    }

    public function test_ayni_adresle_ikinci_bekleyen_basvuru_yapilamaz(): void
    {
        $this->post('/davet/'.$this->davet()->code, $this->basvuruVerisi());

        $this->post('/davet/'.$this->davet()->code, $this->basvuruVerisi())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, MembershipApplication::count());
    }

    public function test_zaten_uye_olan_adres_basvuramaz(): void
    {
        $mevcut = new User();
        $mevcut->name = 'Mevcut';
        $mevcut->email = 'aday@firma.test';
        $mevcut->password = 'sifre12345';
        $mevcut->role = User::ROL_UYE;
        $mevcut->status = 'aktif';
        $mevcut->save();

        $this->post('/davet/'.$this->davet()->code, $this->basvuruVerisi())
            ->assertSessionHasErrors('email');

        $this->assertSame(0, MembershipApplication::count());
    }

    // ------------------------------------------------------------ onay

    public function test_onay_uye_firma_ve_profil_olusturur(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $davet = $this->davet();
        $this->post('/davet/'.$davet->code, $this->basvuruVerisi());
        $basvuru = MembershipApplication::first();

        $this->actingAs($admin)
            ->post(route('yonetim.basvurular.onayla', $basvuru))
            ->assertRedirect(route('yonetim.basvurular.show', $basvuru));

        $uye = User::where('email', 'aday@firma.test')->first();

        $this->assertNotNull($uye);
        $this->assertSame(User::ROL_UYE, $uye->role);
        $this->assertSame('aktif', $uye->status);
        $this->assertTrue($uye->must_change_password, 'gecici sifre zorunlulugu konmali');
        $this->assertSame($admin->id, $uye->approved_by);
        $this->assertNotNull($uye->approved_at);

        // Firma ve profil de olusmali
        $this->assertNotNull($uye->company);
        $this->assertSame('Aday Reklam Ajansi', $uye->company->name);
        $this->assertNotNull($uye->profile);
        $this->assertSame('dnkreatif', $uye->profile->brand);

        // Basvuru ve davet isaretlenmeli
        $basvuru->refresh();
        $this->assertSame('onaylandi', $basvuru->status);
        $this->assertSame($uye->id, $basvuru->created_user_id);
        $this->assertNotNull($davet->fresh()->used_at);
    }

    public function test_onayda_hos_geldin_epostasi_gecici_sifreyle_gonderilir(): void
    {
        Mail::fake();

        $davet = $this->davet();
        $this->post('/davet/'.$davet->code, $this->basvuruVerisi());
        $basvuru = MembershipApplication::first();

        $this->actingAs($this->admin())
            ->post(route('yonetim.basvurular.onayla', $basvuru));

        $uye = User::where('email', 'aday@firma.test')->first();

        Mail::assertSent(HosGeldinMail::class, function (HosGeldinMail $mail) use ($uye) {
            // Gonderilen gecici sifre gercekten hesabin sifresi olmali
            return $mail->hasTo('aday@firma.test')
                && Hash::check($mail->geciciSifre, $uye->password);
        });
    }

    public function test_ayni_isimli_firma_yeniden_olusturulmaz(): void
    {
        Mail::fake();

        $mevcutFirma = Company::create([
            'name' => 'Aday Reklam Ajansi',
            'slug' => 'aday-reklam-ajansi',
        ]);

        $this->post('/davet/'.$this->davet()->code, $this->basvuruVerisi());

        $this->actingAs($this->admin())
            ->post(route('yonetim.basvurular.onayla', MembershipApplication::first()));

        $this->assertSame(1, Company::count(), 'ayni firma icin ikinci kayit acilmamali');
        $this->assertSame(
            $mevcutFirma->id,
            User::where('email', 'aday@firma.test')->first()->company_id
        );
    }

    public function test_ayni_basvuru_iki_kez_onaylanamaz(): void
    {
        Mail::fake();

        $this->post('/davet/'.$this->davet()->code, $this->basvuruVerisi());
        $basvuru = MembershipApplication::first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('yonetim.basvurular.onayla', $basvuru));
        $this->actingAs($admin)->post(route('yonetim.basvurular.onayla', $basvuru))
            ->assertSessionHas('hata');

        $this->assertSame(1, User::where('email', 'aday@firma.test')->count());
    }

    // ------------------------------------------------------------ ret

    public function test_ret_uye_olusturmaz(): void
    {
        Mail::fake();

        $this->post('/davet/'.$this->davet()->code, $this->basvuruVerisi());
        $basvuru = MembershipApplication::first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('yonetim.basvurular.reddet', $basvuru), [
            'admin_note' => 'Is ortagi degil.',
        ]);

        $basvuru->refresh();
        $this->assertSame('reddedildi', $basvuru->status);
        $this->assertSame('Is ortagi degil.', $basvuru->admin_note);
        $this->assertSame($admin->id, $basvuru->reviewed_by);

        $this->assertNull(User::where('email', 'aday@firma.test')->first());
        Mail::assertNothingSent();
    }

    // ------------------------------------------------------------ yetki

    public function test_basvuru_ekranlari_uyeye_kapali(): void
    {
        $this->post('/davet/'.$this->davet()->code, $this->basvuruVerisi());
        $basvuru = MembershipApplication::first();

        $uye = new User();
        $uye->name = 'Sivil Uye';
        $uye->email = 'sivil@dnunity.test';
        $uye->password = 'sifre12345';
        $uye->role = User::ROL_UYE;
        $uye->status = 'aktif';
        $uye->save();

        $this->actingAs($uye)->get(route('yonetim.basvurular.index'))->assertForbidden();
        $this->actingAs($uye)->get(route('yonetim.basvurular.show', $basvuru))->assertForbidden();
        $this->actingAs($uye)->post(route('yonetim.basvurular.onayla', $basvuru))->assertForbidden();
        $this->actingAs($uye)->get(route('yonetim.davetler.index'))->assertForbidden();

        $this->assertSame('beklemede', $basvuru->fresh()->status);
    }

    // ------------------------------------------------------------ davet uretimi

    public function test_admin_davet_bagi_olusturur(): void
    {
        $admin = $this->admin();

        // HTML formu sayilari METIN olarak gonderir; test de oyle gondermeli,
        // aksi halde tip donusumu hatalari testten kacar.
        $this->actingAs($admin)
            ->post(route('yonetim.davetler.store'), ['name' => 'Yeni Aday', 'gun' => '7'])
            ->assertRedirect(route('yonetim.davetler.index'));

        $davet = Invitation::latest('id')->first();

        $this->assertSame('Yeni Aday', $davet->name);
        $this->assertSame($admin->id, $davet->invited_by);
        $this->assertSame(40, strlen($davet->code), 'davet kodu tahmin edilemez uzunlukta olmali');
        $this->assertTrue($davet->expires_at->isFuture());
    }

    public function test_kullanilmis_davet_silinemez(): void
    {
        $admin = $this->admin();
        $davet = $this->davet(['used_at' => now()]);

        $this->actingAs($admin)
            ->delete(route('yonetim.davetler.destroy', $davet))
            ->assertSessionHas('hata');

        $this->assertNotNull($davet->fresh(), 'kayit izi silinmemeli');
    }
}
