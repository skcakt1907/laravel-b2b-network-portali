<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Gorsel yukleme.
 *
 * Guvenlik yaklasimi:
 *  - Tur, istemcinin gonderdigi uzantiya veya Content-Type basligina DEGIL,
 *    dosyanin gercek icerigine (finfo) bakilarak belirlenir.
 *  - Dosya adi tamamen atilir, yerine rastgele ad uretilir.
 *  - Gorsel GD ile yeniden kodlanir. Bu, icine PHP kodu gomulmus "polyglot"
 *    dosyalari etkisiz kilar ve EXIF verisini (konum bilgisi dahil) temizler.
 *  - SVG kabul EDILMEZ: icinde script tasiyabilir.
 *  - Dosyalar public/uploads altinda tutulur (storage symlink'e gerek yok,
 *    paylasimli hosting dostu); o klasorde .htaccess betik calismasini engeller.
 */
class ImageUploader
{
    /** Kabul edilen gercek MIME turleri ve uretilecek uzantilar. */
    public const IZINLI = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** Cozulmus gorselin izin verilen en fazla piksel sayisi (sikistirma bombasi korumasi). */
    private const AZAMI_PIKSEL = 40_000_000;

    private function __construct(
        private readonly string $klasor,
        private readonly int $genislik,
        private readonly int $yukseklik,
        /** true: kirparak tam olcuye getirir (kare avatar). false: orana sadik kalir. */
        private readonly bool $kirp,
        private readonly int $azamiKb,
    ) {}

    public static function avatar(): self
    {
        return new self('avatars', 400, 400, kirp: true, azamiKb: 4096);
    }

    public static function logo(): self
    {
        return new self('logos', 600, 600, kirp: false, azamiKb: 4096);
    }

    public static function kapak(): self
    {
        return new self('kapaklar', 1600, 900, kirp: true, azamiKb: 8192);
    }

    /** Laravel dogrulama kurallari; form istekleri bunu kullanir. */
    public function kurallar(bool $zorunlu = false): array
    {
        return array_filter([
            $zorunlu ? 'required' : 'nullable',
            'file',
            'mimetypes:'.implode(',', array_keys(self::IZINLI)),
            'max:'.$this->azamiKb,
        ]);
    }

    /**
     * Gorseli kaydeder ve public/ icindeki goreli yolu dondurur.
     * Ornek: "uploads/avatars/a1b2....jpg"
     */
    public function yukle(UploadedFile $dosya): string
    {
        if (! $dosya->isValid()) {
            throw new RuntimeException('Dosya yuklenemedi.');
        }

        // Istemcinin bildirdigi tur guvenilmez; gercek icerige bakilir
        $mime = $dosya->getMimeType();

        if (! isset(self::IZINLI[$mime])) {
            throw new RuntimeException('Desteklenmeyen dosya turu.');
        }

        $olcu = @getimagesize($dosya->getRealPath());

        if ($olcu === false) {
            throw new RuntimeException('Dosya gecerli bir gorsel degil.');
        }

        if ($olcu[0] * $olcu[1] > self::AZAMI_PIKSEL) {
            throw new RuntimeException('Gorsel cozunurlugu cok yuksek.');
        }

        $kaynak = $this->ac($dosya->getRealPath(), $mime);

        if ($kaynak === null) {
            throw new RuntimeException('Gorsel okunamadi.');
        }

        try {
            if ($mime === 'image/jpeg') {
                $kaynak = $this->yonuDuzelt($kaynak, $dosya->getRealPath());
            }

            $hedef = $this->olcekle($kaynak);
            $saydam = $this->saydamMi($kaynak, $mime);
        } finally {
            imagedestroy($kaynak);
        }

        // Saydamlik varsa PNG, yoksa JPEG: kucuk dosya + dogru gorunum
        $uzanti = $saydam ? 'png' : 'jpg';
        $ad = Str::lower(Str::random(40)).'.'.$uzanti;
        $klasorYolu = public_path('uploads/'.$this->klasor);

        if (! is_dir($klasorYolu) && ! mkdir($klasorYolu, 0755, true) && ! is_dir($klasorYolu)) {
            throw new RuntimeException('Yukleme klasoru olusturulamadi.');
        }

        $tamYol = $klasorYolu.DIRECTORY_SEPARATOR.$ad;

        try {
            $yazildi = $saydam
                ? imagepng($hedef, $tamYol, 8)
                : imagejpeg($hedef, $tamYol, 85);
        } finally {
            imagedestroy($hedef);
        }

        if (! $yazildi) {
            throw new RuntimeException('Gorsel kaydedilemedi.');
        }

        return 'uploads/'.$this->klasor.'/'.$ad;
    }

    /**
     * Daha once yuklenmis gorseli siler.
     * Yol disaridan gelebilecegi icin uploads klasorunun disina cikilamaz.
     */
    public function sil(?string $yol): bool
    {
        if ($yol === null || $yol === '') {
            return false;
        }

        $kok = realpath(public_path('uploads'));
        $hedef = realpath(public_path($yol));

        if ($kok === false || $hedef === false) {
            return false;
        }

        // Yol gezinme (../) denemelerine karsi: hedef gercekten uploads altinda mi
        if (! str_starts_with($hedef, $kok.DIRECTORY_SEPARATOR)) {
            return false;
        }

        return is_file($hedef) && unlink($hedef);
    }

    // ---------------------------------------------------------------- ic isler

    private function ac(string $yol, string $mime): ?\GdImage
    {
        $g = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($yol),
            'image/png' => @imagecreatefrompng($yol),
            'image/webp' => @imagecreatefromwebp($yol),
            default => false,
        };

        return $g === false ? null : $g;
    }

    /** Telefonla cekilen fotograflarin yan yatmasini onler. */
    private function yonuDuzelt(\GdImage $gorsel, string $yol): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $gorsel;
        }

        $veri = @exif_read_data($yol);
        $yon = $veri['Orientation'] ?? null;

        $aci = match ($yon) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => null,
        };

        if ($aci === null) {
            return $gorsel;
        }

        $donmus = imagerotate($gorsel, $aci, 0);

        if ($donmus === false) {
            return $gorsel;
        }

        imagedestroy($gorsel);

        return $donmus;
    }

    private function saydamMi(\GdImage $gorsel, string $mime): bool
    {
        return $mime !== 'image/jpeg' && imageistruecolor($gorsel)
            ? $this->alfaVarMi($gorsel)
            : false;
    }

    private function alfaVarMi(\GdImage $gorsel): bool
    {
        $g = imagesx($gorsel);
        $y = imagesy($gorsel);

        // Tam tarama buyuk gorsellerde pahali; orneklem yeterli
        $adim = max(1, (int) floor(min($g, $y) / 40));

        for ($x = 0; $x < $g; $x += $adim) {
            for ($i = 0; $i < $y; $i += $adim) {
                if (((imagecolorat($gorsel, $x, $i) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function olcekle(\GdImage $kaynak): \GdImage
    {
        $kg = imagesx($kaynak);
        $ky = imagesy($kaynak);

        if ($this->kirp) {
            // Kapla: kisa kenari hedefe getir, tasan kismi ortadan kirp
            $oran = max($this->genislik / $kg, $this->yukseklik / $ky);
            $hg = $this->genislik;
            $hy = $this->yukseklik;
            $kesG = (int) round($this->genislik / $oran);
            $kesY = (int) round($this->yukseklik / $oran);
            $kesX = (int) round(($kg - $kesG) / 2);
            $kesYy = (int) round(($ky - $kesY) / 2);
        } else {
            // Sigdir: orani bozmadan kutunun icine yerlestir, buyutme yapma
            $oran = min($this->genislik / $kg, $this->yukseklik / $ky, 1);
            $hg = max(1, (int) round($kg * $oran));
            $hy = max(1, (int) round($ky * $oran));
            $kesG = $kg;
            $kesY = $ky;
            $kesX = 0;
            $kesYy = 0;
        }

        $hedef = imagecreatetruecolor($hg, $hy);

        // Saydamligi koru
        imagealphablending($hedef, false);
        imagesavealpha($hedef, true);
        imagefill($hedef, 0, 0, imagecolorallocatealpha($hedef, 0, 0, 0, 127));

        imagecopyresampled($hedef, $kaynak, 0, 0, $kesX, $kesYy, $hg, $hy, $kesG, $kesY);

        return $hedef;
    }
}
