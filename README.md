# B2B Network Portali

Kapali devre uyelik ve is ortakligi basvurulari icin B2B portal.

## Ozellikler

- Uyelik basvurusu, degerlendirme ve durum takibi
- Marka bazli profil ayrimi
- Uye paneli ve yonetici onay akisi
- Uctan uca testler (E2E)

## Kullanilan teknolojiler

Laravel 13 - PHP 8.3 - MySQL - Blade

## Bu depo hakkinda

Gercek bir musteri projesinin **portfolyo icin yayinlanmis** surumudur.
Yayina hazirlanirken canli alan adlari, gercek iletisim bilgileri, musteri
kayitlari ve uygulama anahtarlari ornek degerlerle degistirilmistir.
Kod ve mimari oldugu gibidir; veri gercek degildir.

## Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
