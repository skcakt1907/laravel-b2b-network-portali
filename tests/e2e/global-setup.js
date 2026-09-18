import { execFileSync } from 'node:child_process';

const PHP = 'C:/wamp64/bin/php/php8.3.28/php.exe';

const PROJE = new URL('../..', import.meta.url).pathname.replace(/^\//, '');

function artisan(argumanlar, hataMesaji) {
    try {
        execFileSync(PHP, ['artisan', ...argumanlar, '--no-ansi'], {
            cwd: PROJE,
            stdio: 'pipe',
        });
    } catch (hata) {
        const cikti = [hata.stdout, hata.stderr].filter(Boolean).join('\n');
        throw new Error(`${hataMesaji}\n${cikti}`);
    }
}

/**
 * Her Playwright kosusundan once ortami bilinen duruma getirir.
 */
export default function globalSetup() {
    // Test hesaplari: bir onceki kosunun sifre degistirmesi bu kosuyu bozmasin
    artisan(
        ['db:seed', '--class=E2ESeeder', '--force'],
        'E2E test hesaplari hazirlanamadi. WAMP/MySQL calisiyor mu?'
    );

    // Hiz siniri sayaclari onbellekte tutulur. Arka arkaya calistirilan iki kosu
    // /sifremi-unuttum uzerindeki "dakikada 6 istek" sinirini birlikte asiyor ve
    // ikinci kosu 429 aliyordu. Sayaclari sifirlamak sinirin kendisini zayiflatmaz.
    artisan(['cache:clear'], 'Onbellek temizlenemedi.');
}
