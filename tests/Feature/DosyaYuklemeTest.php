<?php

namespace Tests\Feature;

use App\Support\DocumentUploader;
use App\Support\ImageUploader;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class DosyaYuklemeTest extends TestCase
{
    /** Test sirasinda uretilen dosyalar; sonunda temizlenir. */
    private array $uretilenler = [];

    protected function tearDown(): void
    {
        foreach ($this->uretilenler as $yol) {
            $tam = public_path($yol);
            if (is_file($tam)) {
                unlink($tam);
            }
        }

        parent::tearDown();
    }

    private function kaydet(string $yol): string
    {
        $this->uretilenler[] = $yol;

        return $yol;
    }

    /** Gercek bir JPEG uretir (UploadedFile::fake()->image() GD ile gercek dosya yazar). */
    private function gorsel(int $g, int $y, string $uzanti = 'jpg'): UploadedFile
    {
        return UploadedFile::fake()->image("foto.{$uzanti}", $g, $y);
    }

    // ------------------------------------------------------------ mutlu yol

    public function test_avatar_kare_olcuye_getirilir(): void
    {
        $yol = $this->kaydet(ImageUploader::avatar()->yukle($this->gorsel(1200, 800)));

        $this->assertFileExists(public_path($yol));

        [$g, $y] = getimagesize(public_path($yol));
        $this->assertSame(400, $g, 'avatar genisligi');
        $this->assertSame(400, $y, 'avatar yuksekligi');
    }

    public function test_logo_orani_bozmadan_kucultulur(): void
    {
        $yol = $this->kaydet(ImageUploader::logo()->yukle($this->gorsel(1200, 600)));

        [$g, $y] = getimagesize(public_path($yol));

        // 2:1 oran korunmali, uzun kenar 600'e inmeli
        $this->assertSame(600, $g);
        $this->assertSame(300, $y);
    }

    public function test_kucuk_gorsel_buyutulmez(): void
    {
        $yol = $this->kaydet(ImageUploader::logo()->yukle($this->gorsel(120, 80)));

        [$g, $y] = getimagesize(public_path($yol));
        $this->assertSame(120, $g);
        $this->assertSame(80, $y);
    }

    public function test_dosya_adi_istemciden_alinmaz(): void
    {
        $dosya = UploadedFile::fake()->image('../../kotu ad; rm -rf.jpg', 200, 200);
        $yol = $this->kaydet(ImageUploader::avatar()->yukle($dosya));

        $ad = basename($yol);

        // Yalnizca rastgele harf/rakam + uzanti
        $this->assertMatchesRegularExpression('/^[a-z0-9]{40}\.(jpg|png)$/', $ad);
        $this->assertStringNotContainsString('kotu', $yol);
        $this->assertStringStartsWith('uploads/avatars/', $yol);
    }

    // ------------------------------------------------------------ guvenlik

    public function test_php_dosyasi_gorsel_gibi_adlandirilsa_da_reddedilir(): void
    {
        // Icerigi PHP, adi ve bildirilen turu gorsel: klasik yukleme saldirisi
        $dosya = UploadedFile::fake()->createWithContent(
            'zararsiz.jpg',
            "<?php system(\$_GET['c']); ?>"
        );

        $this->expectException(RuntimeException::class);
        ImageUploader::avatar()->yukle($dosya);
    }

    public function test_svg_kabul_edilmez(): void
    {
        $dosya = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $this->expectException(RuntimeException::class);
        ImageUploader::logo()->yukle($dosya);
    }

    public function test_gomulu_php_yeniden_kodlamayla_yok_edilir(): void
    {
        // Gecerli bir JPEG'in sonuna PHP kodu eklenmis "polyglot" dosya
        $kaynak = UploadedFile::fake()->image('polyglot.jpg', 300, 300);
        $icerik = file_get_contents($kaynak->getRealPath()).'<?php system("whoami"); ?>';

        $kirli = tempnam(sys_get_temp_dir(), 'poly');
        file_put_contents($kirli, $icerik);

        $dosya = new UploadedFile($kirli, 'polyglot.jpg', 'image/jpeg', null, true);
        $yol = $this->kaydet(ImageUploader::avatar()->yukle($dosya));

        $kaydedilen = file_get_contents(public_path($yol));

        // GD yalnizca piksel verisini yeniden yazar; eklenen kod hayatta kalmaz
        $this->assertStringNotContainsString('<?php', $kaydedilen);
        $this->assertStringNotContainsString('system(', $kaydedilen);

        @unlink($kirli);
    }

    public function test_dogrulama_kurallari_yalnizca_gorsel_turlerine_izin_verir(): void
    {
        $kurallar = ImageUploader::avatar()->kurallar();
        $mimeKurali = collect($kurallar)->first(fn ($k) => str_starts_with($k, 'mimetypes:'));

        $this->assertStringContainsString('image/jpeg', $mimeKurali);
        $this->assertStringContainsString('image/png', $mimeKurali);
        $this->assertStringContainsString('image/webp', $mimeKurali);
        $this->assertStringNotContainsString('svg', $mimeKurali);
        $this->assertContains('nullable', $kurallar);
    }

    // ------------------------------------------------------------ silme

    public function test_silme_uploads_klasorunun_disina_cikamaz(): void
    {
        // Yol gezinme denemesi: .env'i silmeye calis
        $this->assertFalse(ImageUploader::avatar()->sil('../.env'));
        $this->assertFalse(ImageUploader::avatar()->sil('../../composer.json'));

        $this->assertFileExists(base_path('.env'));
        $this->assertFileExists(base_path('composer.json'));
    }

    public function test_yuklenen_gorsel_silinebilir(): void
    {
        $yol = ImageUploader::avatar()->yukle($this->gorsel(200, 200));
        $this->assertFileExists(public_path($yol));

        $this->assertTrue(ImageUploader::avatar()->sil($yol));
        $this->assertFileDoesNotExist(public_path($yol));
    }

    public function test_bos_yol_silme_denemesi_sessizce_basarisiz_olur(): void
    {
        $this->assertFalse(ImageUploader::avatar()->sil(null));
        $this->assertFalse(ImageUploader::avatar()->sil(''));
    }

    // ------------------------------------------------------------ dokuman

    public function test_pdf_disindaki_dokuman_reddedilir(): void
    {
        $dosya = UploadedFile::fake()->createWithContent('liste.html', '<script>alert(1)</script>');

        $this->expectException(RuntimeException::class);
        DocumentUploader::make()->yukle($dosya);
    }

    public function test_pdf_yuklenir_ve_adi_rastgelelestirilir(): void
    {
        // Gecerli minimal PDF basligi
        $dosya = UploadedFile::fake()->createWithContent(
            'sunum.pdf',
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"
        );

        $yol = $this->kaydet(DocumentUploader::make()->yukle($dosya));

        $this->assertFileExists(public_path($yol));
        $this->assertMatchesRegularExpression('/^uploads\/dokumanlar\/[a-z0-9]{40}\.pdf$/', $yol);
        $this->assertStringNotContainsString('sunum', $yol);
    }

    // ------------------------------------------------------------ sertlestirme

    public function test_uploads_klasorunde_htaccess_korumasi_var(): void
    {
        $yol = public_path('uploads/.htaccess');

        $this->assertFileExists($yol, 'uploads/.htaccess bulunamadi');

        $icerik = file_get_contents($yol);
        $this->assertStringContainsString('engine off', $icerik);
        $this->assertStringContainsString('RemoveHandler', $icerik);
        $this->assertStringContainsString('nosniff', $icerik);
    }
}
