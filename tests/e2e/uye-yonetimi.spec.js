import { test, expect } from '@playwright/test';

const ADMIN = { email: 'admin@dnunity.com', password: 'admin123' };

/** Dondurma testi durum uretir; her proje kendi hesabini kullanir. */
const dondurulacak = (proje) => `e2e-dondur-${proje}@dnunity.test`;

async function girisYap(page, eposta, sifre) {
    await page.goto('/giris');
    await page.fill('input[name="email"]', eposta);
    await page.fill('input[name="password"]', sifre);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Uye yonetimi ekrani', () => {
    test('liste acilir ve duruma gore suzulur', async ({ page }) => {
        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim/uyeler');

        await expect(page.locator('h1')).toContainText('Uye yonetimi');
        await expect(page.locator('body')).toContainText('admin@dnunity.com');

        await page.click('a:has-text("Dondurulmus")');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/durum=donduruldu/);
    });

    test('admin kendi hesabinda islem yapamaz', async ({ page }) => {
        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim/uyeler?q=admin@dnunity.com');

        await page.locator('tr', { hasText: 'admin@dnunity.com' })
            .getByRole('link', { name: 'Ac' }).click();
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('Bu sizin hesabiniz');
        await expect(page.locator('button:has-text("Dondur")')).toHaveCount(0);
        await expect(page.locator('button:has-text("Kisisel verileri sil")')).toHaveCount(0);
    });

    test('uye yonetim ekranina giremez', async ({ page }) => {
        await girisYap(page, 'e2e-aktif@dnunity.test', 'test12345');

        const yanit = await page.goto('/yonetim/uyeler');
        expect(yanit.status()).toBe(403);
    });
});

test.describe('Dondurma', () => {
    test('dondurulan uyenin acik oturumu kapanir', async ({ browser }, testInfo) => {
        const eposta = dondurulacak(testInfo.project.name);

        // Iki ayri tarayici baglami: biri uye, biri admin
        const uyeContext = await browser.newContext();
        const adminContext = await browser.newContext();
        const uyeSayfa = await uyeContext.newPage();
        const adminSayfa = await adminContext.newPage();

        try {
            // Uye giris yapar ve panelde durur
            await girisYap(uyeSayfa, eposta, 'test12345');
            await expect(uyeSayfa).toHaveURL(/panel/);

            // Admin ayni anda uyeyi dondurur
            await girisYap(adminSayfa, ADMIN.email, ADMIN.password);
            await adminSayfa.goto('/yonetim/uyeler?q=' + encodeURIComponent(eposta));
            await adminSayfa.locator('tr', { hasText: eposta })
                .getByRole('link', { name: 'Ac' }).click();
            await adminSayfa.waitForLoadState('networkidle');
            await adminSayfa.click('button:has-text("Dondur")');
            await adminSayfa.waitForLoadState('networkidle');
            await expect(adminSayfa.locator('body')).toContainText('Uyelik donduruldu');

            // Uyenin oturumu aciktı; bir sonraki istekte disari atilmali
            await uyeSayfa.goto('/panel');
            await expect(uyeSayfa).toHaveURL(/giris/);
            await expect(uyeSayfa.locator('body')).toContainText('artik aktif degil');
        } finally {
            await uyeContext.close();
            await adminContext.close();
        }
    });

    test('dondurulan uye tekrar giris yapamaz', async ({ page }, testInfo) => {
        await girisYap(page, dondurulacak(testInfo.project.name), 'test12345');

        await expect(page).toHaveURL(/giris/);
        await expect(page.locator('body')).toContainText('donduruldu');
    });
});
