import { test, expect } from '@playwright/test';

const ADMIN = { email: 'admin@dnunity.com', password: 'admin123' };

/**
 * E2ESeeder ile ayni kurallar. Her test kendi daveti ve aday adresiyle
 * calisir; basvuru testleri durum urettigi icin paylasilan hesap
 * sonraki testi bozar.
 */
const davetKodu = (proje, amac) => `e2e${amac}${proje}`.padEnd(40, 'x');
const adayEpostasi = (proje, amac) => `${amac}-${proje}@e2e.test`;

async function adminGiris(page) {
    await page.goto('/giris');
    await page.fill('input[name="email"]', ADMIN.email);
    await page.fill('input[name="password"]', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Davet kapisi', () => {
    test('gecersiz davet kodu form acmaz', async ({ page }) => {
        const yanit = await page.goto('/davet/boyle-bir-kod-yok');
        expect(yanit.status()).toBe(404);
    });

    test('gecerli davet formu acar ve davet edeni gosterir', async ({ page }, testInfo) => {
        const proje = testInfo.project.name;
        await page.goto('/davet/' + davetKodu(proje, 'akis'));

        await expect(page.locator('body')).toContainText('sizi DN Unity');
        await expect(page.locator('input[name="email"]'))
            .toHaveValue(adayEpostasi(proje, 'akis'));
    });

    test('basvuru sayfasi arama motorlarina kapali', async ({ page }, testInfo) => {
        await page.goto('/davet/' + davetKodu(testInfo.project.name, 'akis'));
        await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);
    });
});

test.describe('Uctan uca uyelik akisi', () => {
    test('basvuru -> onay -> uye olusur', async ({ page }, testInfo) => {
        const proje = testInfo.project.name;
        const eposta = adayEpostasi(proje, 'akis');

        // --- 1. Aday davet bagiyla basvurur -------------------------------
        await page.goto('/davet/' + davetKodu(proje, 'akis'));
        await page.fill('input[name="company_name"]', 'E2E Test Ajansi');
        await page.fill('input[name="city"]', 'Bodrum');
        await page.selectOption('select[name="brand"]', 'dnkreatif');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/basvuru\/tesekkur/);
        await expect(page.locator('body')).toContainText('Basvurunuz alindi');

        // --- 2. Admin basvuruyu listede goruyor ---------------------------
        await adminGiris(page);
        await page.goto('/yonetim/basvurular');

        const satir = page.locator('tr', { hasText: eposta });
        await expect(satir).toHaveCount(1);

        // --- 3. Admin onayliyor -------------------------------------------
        page.on('dialog', (d) => d.accept());
        await satir.getByRole('link', { name: 'Incele' }).click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('E2E Test Ajansi');

        await page.click('button:has-text("Onayla ve uye olustur")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('uye olarak eklendi');
        await expect(page.locator('body')).toContainText('Onaylandi');
        // Gecici sifre zorunlulugu konmus olmali
        await expect(page.locator('body')).toContainText('Gecici sifre henuz degistirilmedi');

        // --- 4. Ayni basvuru ikinci kez onaylanamaz -----------------------
        await page.reload();
        await expect(page.locator('button:has-text("Onayla ve uye olustur")')).toHaveCount(0);
    });

    test('ayni adresle ikinci basvuru engellenir', async ({ page }, testInfo) => {
        const proje = testInfo.project.name;
        const bag = '/davet/' + davetKodu(proje, 'tekrar');

        await page.goto(bag);
        await page.fill('input[name="company_name"]', 'E2E Tekrar Ajansi');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/basvuru\/tesekkur/);

        // Davet henuz onaylanmadigi icin bag hala acik; ama ayni adres
        // bekleyen basvurusu oldugu icin tekrar basvuramaz.
        await page.goto(bag);
        await page.fill('input[name="company_name"]', 'Baska Firma');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('zaten bir kayit veya bekleyen basvuru var');
    });
});

test.describe('Davet yonetimi', () => {
    test('admin davet bagi olusturur', async ({ page }) => {
        await adminGiris(page);
        await page.goto('/yonetim/davetler');

        await page.fill('input[name="name"]', 'Playwright Aday');
        await page.fill('input[name="gun"]', '30');
        await page.click('button:has-text("Davet bagi olustur")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('Davet bagi olusturuldu');
        await expect(page.locator('body')).toContainText('Playwright Aday');
    });

    test('davet listesi uyeye kapali', async ({ page }) => {
        await page.goto('/giris');
        await page.fill('input[name="email"]', 'e2e-aktif@dnunity.test');
        await page.fill('input[name="password"]', 'test12345');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');

        const yanit = await page.goto('/yonetim/davetler');
        expect(yanit.status()).toBe(403);
    });
});
