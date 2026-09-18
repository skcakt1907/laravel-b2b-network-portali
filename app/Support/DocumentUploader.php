<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Dokuman yukleme (egitim materyalleri, sunumlar).
 *
 * Gorsellerin aksine dokuman yeniden kodlanamaz; icerigi oldugu gibi saklanir.
 * Bu yuzden savunma iki kata dayanir:
 *  - Gercek MIME denetimi (istemcinin bildirdigine guvenilmez)
 *  - public/uploads/.htaccess: o klasorde hicbir betik calistirilamaz
 *
 * Indirilirken tarayicinin dosyayi yorumlamasini onlemek icin
 * Content-Disposition: attachment ile sunulmalidir.
 */
class DocumentUploader
{
    public const IZINLI = [
        'application/pdf' => 'pdf',
    ];

    private const AZAMI_KB = 20480; // 20 MB

    public static function make(string $klasor = 'dokumanlar'): self
    {
        return new self($klasor);
    }

    private function __construct(private readonly string $klasor) {}

    public function kurallar(bool $zorunlu = false): array
    {
        return array_filter([
            $zorunlu ? 'required' : 'nullable',
            'file',
            'mimetypes:'.implode(',', array_keys(self::IZINLI)),
            'max:'.self::AZAMI_KB,
        ]);
    }

    /**
     * Dosyayi kaydeder, public/ icindeki goreli yolu dondurur.
     * Gorunen ad ayrica saklanmali; disk adi rastgeledir.
     */
    public function yukle(UploadedFile $dosya): string
    {
        if (! $dosya->isValid()) {
            throw new RuntimeException('Dosya yuklenemedi.');
        }

        $mime = $dosya->getMimeType();

        if (! isset(self::IZINLI[$mime])) {
            throw new RuntimeException('Yalnizca PDF yuklenebilir.');
        }

        $klasorYolu = public_path('uploads/'.$this->klasor);

        if (! is_dir($klasorYolu) && ! mkdir($klasorYolu, 0755, true) && ! is_dir($klasorYolu)) {
            throw new RuntimeException('Yukleme klasoru olusturulamadi.');
        }

        // Istemcinin dosya adi tamamen atilir
        $ad = Str::lower(Str::random(40)).'.'.self::IZINLI[$mime];
        $dosya->move($klasorYolu, $ad);

        return 'uploads/'.$this->klasor.'/'.$ad;
    }

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

        if (! str_starts_with($hedef, $kok.DIRECTORY_SEPARATOR)) {
            return false;
        }

        return is_file($hedef) && unlink($hedef);
    }
}
