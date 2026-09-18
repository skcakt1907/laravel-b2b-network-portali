<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GirisTest extends TestCase
{
    use RefreshDatabase;

    /** Belirtilen rol/durumda bir kullanici uretir. */
    private function kullanici(array $ozellikler = []): User
    {
        $u = new User();
        $u->name = $ozellikler['name'] ?? 'Test Uye';
        $u->email = $ozellikler['email'] ?? 'test@dnunity.com';
        $u->password = $ozellikler['password'] ?? 'sifre12345';
        $u->role = $ozellikler['role'] ?? User::ROL_UYE;
        $u->status = $ozellikler['status'] ?? 'aktif';
        $u->must_change_password = $ozellikler['must_change_password'] ?? false;
        $u->guest_expires_at = $ozellikler['guest_expires_at'] ?? null;
        $u->save();

        return $u;
    }

    // ------------------------------------------------------------ giris

    public function test_aktif_uyeyi_panele_alir(): void
    {
        $this->kullanici();

        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345'])
            ->assertRedirect(route('panel'));

        $this->assertAuthenticated();
    }

    public function test_hatali_sifreyi_reddeder(): void
    {
        $this->kullanici();

        $this->from('/giris')
            ->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'yanlis'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_onay_bekleyen_hesabi_iceri_almaz(): void
    {
        $this->kullanici(['status' => 'beklemede']);

        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_dondurulmus_hesabi_iceri_almaz(): void
    {
        $this->kullanici(['status' => 'donduruldu']);

        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suresi_dolmus_misafiri_iceri_almaz(): void
    {
        $this->kullanici([
            'role' => User::ROL_MISAFIR,
            'guest_expires_at' => now()->subDay(),
        ]);

        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suresi_dolmamis_misafiri_iceri_alir(): void
    {
        $this->kullanici([
            'role' => User::ROL_MISAFIR,
            'guest_expires_at' => now()->addWeek(),
        ]);

        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345'])
            ->assertRedirect(route('panel'));

        $this->assertAuthenticated();
    }

    public function test_giris_aninda_son_giris_zamanini_yazar(): void
    {
        $u = $this->kullanici();
        $this->assertNull($u->last_login_at);

        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345']);

        $this->assertNotNull($u->fresh()->last_login_at);
    }

    public function test_besinci_denemeden_sonra_hiz_siniri_uygular(): void
    {
        $this->kullanici();

        foreach (range(1, 5) as $_) {
            $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'yanlis']);
        }

        // Altinci denemede sifre DOGRU olsa bile kilit devrede olmali
        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ------------------------------------------------------------ gecici sifre

    public function test_gecici_sifreli_uyeyi_sifre_degistirmeye_zorlar(): void
    {
        $this->kullanici(['must_change_password' => true]);

        $this->post('/giris', ['email' => 'test@dnunity.com', 'password' => 'sifre12345'])
            ->assertRedirect(route('sifre.degistir'));

        // Panele gitmeye calissa bile geri gonderilir
        $this->get('/panel')->assertRedirect(route('sifre.degistir'));
    }

    public function test_sifre_degistirince_zorunluluk_kalkar(): void
    {
        $u = $this->kullanici(['must_change_password' => true]);
        $this->actingAs($u);

        $this->post('/sifre-degistir', [
            'current_password' => 'sifre12345',
            'password' => 'yeniSifre2026',
            'password_confirmation' => 'yeniSifre2026',
        ])->assertRedirect(route('panel'));

        $this->assertFalse($u->fresh()->must_change_password);
    }

    public function test_yeni_sifre_eskisiyle_ayni_olamaz(): void
    {
        $u = $this->kullanici(['must_change_password' => true]);
        $this->actingAs($u);

        $this->post('/sifre-degistir', [
            'current_password' => 'sifre12345',
            'password' => 'sifre12345',
            'password_confirmation' => 'sifre12345',
        ])->assertSessionHasErrors('password');

        $this->assertTrue($u->fresh()->must_change_password);
    }

    // ------------------------------------------------------------ sifre sifirlama

    public function test_sifre_sifirlama_bagini_gonderir(): void
    {
        Notification::fake();
        $u = $this->kullanici();

        $this->post('/sifremi-unuttum', ['email' => 'test@dnunity.com'])
            ->assertSessionHas('basari');

        Notification::assertSentTo($u, ResetPassword::class);
    }

    public function test_kayitsiz_adres_icin_ayni_mesaji_doner(): void
    {
        Notification::fake();

        $this->post('/sifremi-unuttum', ['email' => 'yok@dnunity.com'])
            ->assertSessionHas('basari');

        Notification::assertNothingSent();
    }

    // ------------------------------------------------------------ erisim

    public function test_girisi_olmayani_girise_yonlendirir(): void
    {
        $this->get('/panel')->assertRedirect(route('giris'));
    }

    public function test_cikis_oturumu_kapatir(): void
    {
        $this->actingAs($this->kullanici())
            ->post('/cikis')
            ->assertRedirect(route('giris'));

        $this->assertGuest();
    }
}
