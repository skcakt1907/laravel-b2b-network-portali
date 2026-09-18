import { defineConfig, devices } from '@playwright/test';

const PHP = 'C:/wamp64/bin/php/php8.3.28/php.exe';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30000,
    reporter: [['list']],

    /*
     * Testler tek bir gercek veritabanina ve tek bir hiz siniri sayacina karsi
     * kosuyor. Playwright varsayilan olarak spec dosyalarini paralel iscilere
     * dagitir; bu durumda testler birbirinin verisine ve hiz sinirina takilir
     * (kosudan kosuya degisen, tekrar uretilemeyen hatalar). Tek isci
     * kosuyu yavaslatir ama deterministik yapar.
     */
    fullyParallel: false,
    workers: 1,

    // Testlerin dayandigi sabit hesaplari her kosudan once yeniler
    globalSetup: './tests/e2e/global-setup.js',

    /*
     * WAMP adresi (localhost/dnunity/public) baseURL olarak kullanilamaz:
     * Playwright yolu new URL() ile cozer, bastaki "/" alt klasoru silip
     * istegi localhost koküne gonderir. Bu yuzden testler kendi sunucusunu
     * kok adreste calistirir - optik-shop ile ayni duzen.
     */
    webServer: {
        command: `${PHP} artisan serve --host=127.0.0.1 --port=8000`,
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: true,
        timeout: 60000,
    },

    use: {
        baseURL: 'http://127.0.0.1:8000',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },

    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobil', use: { ...devices['Pixel 7'] } },
    ],
});
