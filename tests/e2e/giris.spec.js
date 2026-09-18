import { test, expect } from '@playwright/test';

const SIFRE = 'test12345';

const ADMIN = { email: 'admin@dnunity.com', password: 'admin123' };
const AKTIF = { email: 'e2e-aktif@dnunity.test', password: SIFRE };
const BEKLEMEDE = { email: 'e2e-beklemede@dnunity.test', password: SIFRE };
const DONDURULDU = { email: 'e2e-donduruldu@dnunity.test', password: SIFRE };
const MISAFIR_DOLMUS = { email: 'e2e-misafir-dolmus@dnunity.test', password: SIFRE };

/**
 * Sifre degistirme testi hesabi tuketir. Projeler ayni veritabanini
 * paylastigi icin her proje kendi hesabini kullanir.
 */
function geciciHesap(testInfo) {
    return { email: `e2e-gecici-${testInfo.project.name}@dnunity.test`, password: SIFRE };
}

async function girisDene(page, { email, password }) {
    await page.goto('/giris');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

// ---------------------------------------------------------------- erisim kapisi

test.describe('Kapali devre erisim', () => {
    test('giris yapmayan ziyaretci panele giremez', async ({ page }) => {
        await page.goto('/panel');
        await expect(page).toHaveURL(/giris/);
    });

    test('ana sayfa girise yonlendirir', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveURL(/giris/);
        await expect(page.locator('input[name="email"]')).toBeVisible();
    });

    test('giris ekrani kapali devre oldugunu soyler', async ({ page }) => {
        await page.goto('/giris');
        await expect(page.locator('body')).toContainText('kapali devre');
    });

    test('arama motorlarina kapali', async ({ page }) => {
        await page.goto('/giris');
        const robots = page.locator('meta[name="robots"]');
        await expect(robots).toHaveAttribute('content', /noindex/);
    });
});

// ---------------------------------------------------------------- giris

test.describe('Giris', () => {
    test('hatali sifre reddedilir', async ({ page }) => {
        await girisDene(page, { email: AKTIF.email, password: 'kesinlikle-yanlis' });

        await expect(page).toHaveURL(/giris/);
        await expect(page.locator('body')).toContainText('E-posta veya sifre hatali');
    });

    test('aktif uye panele girer', async ({ page }) => {
        await girisDene(page, AKTIF);

        await expect(page).toHaveURL(/panel/);
        await expect(page.locator('h1')).toContainText('Panel');
        await expect(page.locator('body')).toContainText('E2E Aktif Uye');
    });

    test('onay bekleyen hesap giremez ve sebebini ogrenir', async ({ page }) => {
        await girisDene(page, BEKLEMEDE);

        await expect(page).toHaveURL(/giris/);
        await expect(page.locator('body')).toContainText('henuz onaylanmadi');
    });

    test('dondurulmus hesap giremez', async ({ page }) => {
        await girisDene(page, DONDURULDU);

        await expect(page).toHaveURL(/giris/);
        await expect(page.locator('body')).toContainText('donduruldu');
    });

    test('suresi dolmus misafir giremez', async ({ page }) => {
        await girisDene(page, MISAFIR_DOLMUS);

        await expect(page).toHaveURL(/giris/);
        await expect(page.locator('body')).toContainText('suresi dolmus');
    });

    test('cikis yapinca oturum kapanir', async ({ page }) => {
        await girisDene(page, AKTIF);
        await expect(page).toHaveURL(/panel/);

        await page.click('.topbar .dropdown-toggle');
        await page.click('.topbar button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/giris/);

        // Oturum gercekten kapandi mi: panele dogrudan gitmeyi dene
        await page.goto('/panel');
        await expect(page).toHaveURL(/giris/);
    });
});

// ---------------------------------------------------------------- roller

test.describe('Rol gorunurlugu', () => {
    test('admin yonetim menusunu gorur', async ({ page }) => {
        await girisDene(page, ADMIN);

        await expect(page.locator('.sidebar')).toContainText('Basvurular');
        await expect(page.locator('.sidebar')).toContainText('Coin Yonetimi');
    });

    test('normal uye yonetim menusunu GORMEZ', async ({ page }) => {
        await girisDene(page, AKTIF);

        await expect(page.locator('.sidebar')).toContainText('Uye Dizini');
        await expect(page.locator('.sidebar')).not.toContainText('Basvurular');
        await expect(page.locator('.sidebar')).not.toContainText('Coin Yonetimi');
    });
});

// ---------------------------------------------------------------- gecici sifre

test.describe('Zorunlu sifre degistirme', () => {
    test('gecici sifreli uye baska sayfaya gecemez', async ({ page }, testInfo) => {
        await girisDene(page, geciciHesap(testInfo));

        await expect(page).toHaveURL(/sifre-degistir/);

        // Panele zorlamayi dene: yine sifre ekranina donmeli
        await page.goto('/panel');
        await expect(page).toHaveURL(/sifre-degistir/);
    });

    test('sifre degistirince panele gecer', async ({ page }, testInfo) => {
        await girisDene(page, geciciHesap(testInfo));
        await expect(page).toHaveURL(/sifre-degistir/);

        await page.fill('input[name="current_password"]', SIFRE);
        await page.fill('input[name="password"]', 'YeniSifre2026');
        await page.fill('input[name="password_confirmation"]', 'YeniSifre2026');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/panel/);
        await expect(page.locator('body')).toContainText('hos geldiniz');
    });
});

// ---------------------------------------------------------------- sifre sifirlama

test.describe('Sifre sifirlama', () => {
    test('kayitli adres icin onay mesaji doner', async ({ page }) => {
        await page.goto('/sifremi-unuttum');
        await page.fill('input[name="email"]', AKTIF.email);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('e-posta ile gonderildi');
    });

    test('kayitsiz adres icin AYNI mesaj doner (adres sizdirmaz)', async ({ page }) => {
        await page.goto('/sifremi-unuttum');
        await page.fill('input[name="email"]', 'boyle-biri-yok@dnunity.test');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('e-posta ile gonderildi');
    });
});
