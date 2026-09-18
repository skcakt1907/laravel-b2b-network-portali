<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use App\Services\PresentationOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtkinlikTest extends TestCase
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

        if ($rol === User::ROL_MISAFIR) {
            $u->guest_expires_at = now()->addWeek();
        }

        $u->save();

        return $u;
    }

    private function etkinlik(array $ayar = []): Event
    {
        $e = Event::create([
            'title' => $ayar['baslik'] ?? 'Aylik Toplanti '.uniqid(),
            'slug' => 'etkinlik-'.uniqid(),
            'type' => 'online',
            'category' => 'toplanti',
            'starts_at' => $ayar['baslangic'] ?? now()->addWeek(),
            'online_url' => $ayar['bag'] ?? 'https://zoom.us/j/123456',
            'capacity' => $ayar['kontenjan'] ?? null,
            'visibility' => $ayar['gorunurluk'] ?? 'uye',
            'is_published' => $ayar['yayinda'] ?? true,
            'has_presentations' => $ayar['sunum'] ?? false,
        ]);

        return $e;
    }

    private function katilim(Event $e, User $u, string $rsvp = 'katiliyor'): EventAttendee
    {
        return EventAttendee::create([
            'event_id' => $e->id,
            'user_id' => $u->id,
            'rsvp' => $rsvp,
            'responded_at' => now(),
        ]);
    }

    // ------------------------------------------------------------ takvim

    public function test_takvim_yalnizca_yayindaki_etkinlikleri_gosterir(): void
    {
        $uye = $this->kullanici();
        $this->etkinlik(['baslik' => 'Yayindaki Toplanti']);
        $this->etkinlik(['baslik' => 'Taslak Toplanti', 'yayinda' => false]);

        $this->actingAs($uye)->get(route('etkinlikler'))
            ->assertOk()
            ->assertSee('Yayindaki Toplanti')
            ->assertDontSee('Taslak Toplanti');
    }

    public function test_misafir_yalnizca_kendisine_acilan_etkinligi_gorur(): void
    {
        $misafir = $this->kullanici(User::ROL_MISAFIR);
        $this->etkinlik(['baslik' => 'Uyelere Ozel', 'gorunurluk' => 'uye']);
        $this->etkinlik(['baslik' => 'Misafire Acik', 'gorunurluk' => 'uye_misafir']);

        $this->actingAs($misafir)->get(route('etkinlikler'))
            ->assertOk()
            ->assertSee('Misafire Acik')
            ->assertDontSee('Uyelere Ozel');
    }

    public function test_gecmis_etkinlikler_ayri_sekmede(): void
    {
        $uye = $this->kullanici();
        $this->etkinlik(['baslik' => 'Gecmis Toplanti', 'baslangic' => now()->subWeek()]);
        $this->etkinlik(['baslik' => 'Gelecek Toplanti', 'baslangic' => now()->addWeek()]);

        $this->actingAs($uye)->get(route('etkinlikler'))
            ->assertSee('Gelecek Toplanti')
            ->assertDontSee('Gecmis Toplanti');

        $this->actingAs($uye)->get(route('etkinlikler', ['gecmis' => 1]))
            ->assertSee('Gecmis Toplanti')
            ->assertDontSee('Gelecek Toplanti');
    }

    // ------------------------------------------------------------ katilim onayi

    public function test_uye_katilim_onayi_verebilir(): void
    {
        $uye = $this->kullanici();
        $e = $this->etkinlik();

        $this->actingAs($uye)
            ->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katiliyor'])
            ->assertRedirect();

        $this->assertSame('katiliyor', EventAttendee::first()->rsvp);
        $this->assertSame(1, $e->fresh()->katilanSayisi());
    }

    public function test_yanit_degistirilebilir_ve_ikinci_kayit_acilmaz(): void
    {
        $uye = $this->kullanici();
        $e = $this->etkinlik();

        $this->actingAs($uye)->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katiliyor']);
        $this->actingAs($uye)->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katilmiyor']);

        $this->assertSame(1, EventAttendee::count(), 'ayni uye icin ikinci satir acilmamali');
        $this->assertSame('katilmiyor', EventAttendee::first()->rsvp);
    }

    public function test_kontenjan_dolunca_yeni_katilim_alinmaz(): void
    {
        $e = $this->etkinlik(['kontenjan' => 1]);
        $this->katilim($e, $this->kullanici());

        $gec_kalan = $this->kullanici();

        $this->actingAs($gec_kalan)
            ->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katiliyor'])
            ->assertSessionHas('hata');

        $this->assertSame(1, $e->fresh()->katilanSayisi());
    }

    public function test_kontenjan_dolu_olsa_da_mevcut_katilimci_yanitini_yenileyebilir(): void
    {
        $uye = $this->kullanici();
        $e = $this->etkinlik(['kontenjan' => 1]);
        $this->katilim($e, $uye);

        // Zaten listede olan kisi kendi yanitini tekrar gonderince engellenmemeli
        $this->actingAs($uye)
            ->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katiliyor'])
            ->assertSessionMissing('hata');
    }

    public function test_gecmis_etkinlige_katilim_onayi_verilemez(): void
    {
        $uye = $this->kullanici();
        $e = $this->etkinlik(['baslangic' => now()->subDay()]);

        $this->actingAs($uye)
            ->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katiliyor'])
            ->assertForbidden();

        $this->assertSame(0, EventAttendee::count());
    }

    public function test_katilmaktan_vazgecince_sunum_sirasi_dusuyor(): void
    {
        $uye = $this->kullanici();
        $e = $this->etkinlik(['sunum' => true]);
        $katilim = $this->katilim($e, $uye);
        $katilim->forceFill(['presentation_order' => 3])->save();

        $this->actingAs($uye)->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katilmiyor']);

        $this->assertNull($katilim->fresh()->presentation_order);
    }

    // ------------------------------------------------------------ toplanti bagi

    public function test_toplanti_bagi_yalnizca_katilacaklara_gosterilir(): void
    {
        $uye = $this->kullanici();
        $e = $this->etkinlik(['bag' => 'https://zoom.us/j/GIZLIBAG']);

        // Once yanit vermeden: bag gorunmemeli
        $this->actingAs($uye)->get(route('etkinlikler.detay', $e))
            ->assertOk()
            ->assertDontSee('GIZLIBAG');

        $this->actingAs($uye)->post(route('etkinlikler.katilim', $e), ['rsvp' => 'katiliyor']);

        $this->actingAs($uye)->get(route('etkinlikler.detay', $e))
            ->assertSee('GIZLIBAG');
    }

    public function test_misafir_uyelere_ozel_etkinligi_acamaz(): void
    {
        $misafir = $this->kullanici(User::ROL_MISAFIR);
        $e = $this->etkinlik(['gorunurluk' => 'uye']);

        $this->actingAs($misafir)->get(route('etkinlikler.detay', $e))->assertForbidden();
    }

    // ------------------------------------------------------------ sunum sirasi

    public function test_sunum_sirasi_yalnizca_katilacaklara_verilir(): void
    {
        $e = $this->etkinlik(['sunum' => true]);

        $katilan1 = $this->katilim($e, $this->kullanici(), 'katiliyor');
        $katilan2 = $this->katilim($e, $this->kullanici(), 'katiliyor');
        $katilmayan = $this->katilim($e, $this->kullanici(), 'katilmiyor');

        $adet = app(PresentationOrder::class)->uret($e);

        $this->assertSame(2, $adet);
        $this->assertNotNull($katilan1->fresh()->presentation_order);
        $this->assertNotNull($katilan2->fresh()->presentation_order);
        $this->assertNull($katilmayan->fresh()->presentation_order);

        // Siralar 1..n araliginda ve tekrarsiz olmali
        $siralar = EventAttendee::where('event_id', $e->id)
            ->whereNotNull('presentation_order')
            ->pluck('presentation_order')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([1, 2], $siralar);
    }

    public function test_sira_yeniden_uretilince_eski_siralar_temizlenir(): void
    {
        $e = $this->etkinlik(['sunum' => true]);
        $vazgecen = $this->katilim($e, $this->kullanici(), 'katiliyor');

        app(PresentationOrder::class)->uret($e);
        $this->assertNotNull($vazgecen->fresh()->presentation_order);

        // Kisi vazgecer, admin sirayi yeniden uretir
        $vazgecen->forceFill(['rsvp' => 'katilmiyor'])->save();
        app(PresentationOrder::class)->uret($e);

        $this->assertNull($vazgecen->fresh()->presentation_order);
    }

    public function test_admin_sirayi_elle_degistirebilir(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $e = $this->etkinlik(['sunum' => true]);
        $k = $this->katilim($e, $this->kullanici(), 'katiliyor');

        $this->actingAs($admin)->post(route('yonetim.etkinlikler.siraKaydet', $e), [
            'sira' => [$k->id => 7],
        ])->assertRedirect();

        $this->assertSame(7, $k->fresh()->presentation_order);
    }

    // ------------------------------------------------------------ yonetim

    public function test_admin_etkinlik_olusturur(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        $this->actingAs($admin)->post(route('yonetim.etkinlikler.kaydet'), [
            'title' => 'Ekim Networking Toplantisi',
            'type' => 'online',
            'category' => 'toplanti',
            'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'visibility' => 'uye',
            'is_published' => '1',
            'has_presentations' => '1',
        ])->assertRedirect();

        $e = Event::where('title', 'Ekim Networking Toplantisi')->first();

        $this->assertNotNull($e);
        $this->assertSame('ekim-networking-toplantisi', $e->slug);
        $this->assertTrue($e->is_published);
        $this->assertTrue($e->has_presentations);
        $this->assertSame($admin->id, $e->created_by);
    }

    public function test_ayni_baslikta_ikinci_etkinlik_farkli_slug_alir(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);

        $veri = [
            'title' => 'Aylik Toplanti',
            'type' => 'online',
            'category' => 'toplanti',
            'starts_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'visibility' => 'uye',
        ];

        $this->actingAs($admin)->post(route('yonetim.etkinlikler.kaydet'), $veri);
        $this->actingAs($admin)->post(route('yonetim.etkinlikler.kaydet'), $veri);

        $slugler = Event::where('title', 'Aylik Toplanti')->pluck('slug')->all();

        $this->assertCount(2, $slugler);
        $this->assertSame(count($slugler), count(array_unique($slugler)));
    }

    public function test_katilim_verilmis_etkinlik_silinemez(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $e = $this->etkinlik();
        $this->katilim($e, $this->kullanici());

        $this->actingAs($admin)
            ->delete(route('yonetim.etkinlikler.sil', $e))
            ->assertSessionHas('hata');

        $this->assertNotNull($e->fresh());
    }

    public function test_uye_etkinlik_yonetimine_erisemez(): void
    {
        $uye = $this->kullanici();
        $e = $this->etkinlik();

        $this->actingAs($uye)->get(route('yonetim.etkinlikler.index'))->assertForbidden();
        $this->actingAs($uye)->get(route('yonetim.etkinlikler.duzenle', $e))->assertForbidden();
        $this->actingAs($uye)->post(route('yonetim.etkinlikler.sirala', $e))->assertForbidden();
    }

    // ------------------------------------------------------------ disa aktarim

    public function test_katilimci_listesi_csv_olarak_indirilir(): void
    {
        $admin = $this->kullanici(User::ROL_ADMIN);
        $e = $this->etkinlik(['baslik' => 'Kasim Toplantisi']);

        $uye = $this->kullanici();
        $uye->forceFill(['name' => 'Katilan Uye'])->save();
        $this->katilim($e, $uye);

        $yanit = $this->actingAs($admin)->get(route('yonetim.etkinlikler.disaAktar', $e));

        $yanit->assertOk();
        $yanit->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $icerik = $yanit->streamedContent();

        // Excel'in UTF-8 olarak acmasi icin BOM ile baslamali
        $this->assertStringStartsWith("\xEF\xBB\xBF", $icerik);
        $this->assertStringContainsString('Katilan Uye', $icerik);
        // CSV, bosluk iceren basliklari tirnak icine alir
        $this->assertStringContainsString('"Sunum Sirasi";"Ad Soyad"', $icerik);
        $this->assertStringContainsString('Katiliyor', $icerik);
    }
}
