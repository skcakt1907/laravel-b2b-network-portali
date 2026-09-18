import { test, expect } from '@playwright/test';

const ADMIN = { email: 'admin@dnunity.com', password: 'admin123' };
const UYE = { email: 'e2e-aktif@dnunity.test', password: 'test12345' };
const MISAFIR = { email: 'e2e-misafir@dnunity.test', password: 'test12345' };

/** RSVP testi durum uretir; her proje kendi etkinliginde calisir. */
const etkinlikSlug = (proje) => `e2e-rsvp-toplantisi-${proje}`;
const uyeAcikSlug = (proje) => `e2e-uyelere-ozel-${proje}`;

async function girisYap(page, eposta, sifre = 'test12345') {
    // Ayni test icinde ikinci kez giris yapilabiliyor (once uye, sonra admin).
    // Oturum acikken /giris yonlendirir ve form bulunamaz; once cerezler silinir.
    await page.context().clearCookies();
    await page.goto('/giris');
    await page.fill('input[name="email"]', eposta);
    await page.fill('input[name="password"]', sifre);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Etkinlik takvimi', () => {
    test('yaklasan ve gecmis sekmeleri calisir', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/etkinlikler');

        await expect(page.locator('h1')).toContainText('Etkinlikler');

        await page.click('a:has-text("Gecmis")');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/gecmis=1/);
    });

    test('misafir uyelere ozel etkinligi goremez', async ({ page }, testInfo) => {
        await girisYap(page, MISAFIR.email);

        const yanit = await page.goto('/etkinlikler/' + uyeAcikSlug(testInfo.project.name));
        expect(yanit.status()).toBe(403);
    });

    test('misafir kendisine acik etkinligi gorebilir', async ({ page }, testInfo) => {
        await girisYap(page, MISAFIR.email);

        const yanit = await page.goto('/etkinlikler/' + etkinlikSlug(testInfo.project.name));
        expect(yanit.status()).toBe(200);
    });
});

test.describe('Katilim onayi', () => {
    test('toplanti bagi yalnizca katilacaklara gosterilir', async ({ page }, testInfo) => {
        const slug = etkinlikSlug(testInfo.project.name);

        await girisYap(page, UYE.email);
        await page.goto('/etkinlikler/' + slug);

        // Once yanit yok: bag gizli
        await expect(page.locator('body')).toContainText('katilacaginizi bildirdikten sonra gorunur');
        await expect(page.locator('a:has-text("Toplantiya katil")')).toHaveCount(0);

        // Katiliyorum
        await page.click('button:has-text("Katiliyorum")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('Katiliminiz kaydedildi');
        await expect(page.locator('a:has-text("Toplantiya katil")')).toBeVisible();

        // Vazgecince bag yeniden gizlenir
        await page.click('button:has-text("Katilamiyorum")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('a:has-text("Toplantiya katil")')).toHaveCount(0);
    });

    test('katilim rozeti takvimde gorunur', async ({ page }, testInfo) => {
        const slug = etkinlikSlug(testInfo.project.name);

        await girisYap(page, UYE.email);
        await page.goto('/etkinlikler/' + slug);
        await page.click('button:has-text("Katiliyorum")');
        await page.waitForLoadState('networkidle');

        await page.goto('/etkinlikler');
        await expect(page.locator('body')).toContainText('Katiliyorsunuz');
    });
});

test.describe('Etkinlik yonetimi', () => {
    test('admin etkinlik olusturur ve yayindan kaldirir', async ({ page }, testInfo) => {
        const baslik = `E2E Olusturulan Etkinlik ${testInfo.project.name} ${Date.now()}`;

        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim/etkinlikler/olustur');

        await page.fill('#title', baslik);
        await page.fill('#starts_at', '2027-03-15T20:00');
        await page.check('#is_published');
        await page.click('button:has-text("Olustur")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('Etkinlik olusturuldu');
        await expect(page.locator('#title')).toHaveValue(baslik);

        // Yayindan kaldir
        await page.uncheck('#is_published');
        await page.click('button:has-text("Kaydet")');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('#is_published')).not.toBeChecked();
    });

    test('sunum sirasi uretilir ve yalnizca katilanlara verilir', async ({ page }, testInfo) => {
        const slug = etkinlikSlug(testInfo.project.name);

        // Once bir uye katilim bildirsin
        await girisYap(page, UYE.email);
        await page.goto('/etkinlikler/' + slug);
        await page.click('button:has-text("Katiliyorum")');
        await page.waitForLoadState('networkidle');

        // Admin sirayi uretsin
        await girisYap(page, ADMIN.email, ADMIN.password);
        page.on('dialog', (d) => d.accept());
        await page.goto('/yonetim/etkinlikler/' + slug);
        await page.click('button:has-text("Sunum sirasini uret")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('sunum sirasi olusturuldu');

        // Sira kutularindan en az biri dolu olmali
        const ilkSira = page.locator('input[name^="sira["]').first();
        await expect(ilkSira).toHaveValue(/\d+/);
    });

    test('katilimci listesi CSV olarak indirilir', async ({ page }, testInfo) => {
        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim/etkinlikler/' + etkinlikSlug(testInfo.project.name));

        const [indirme] = await Promise.all([
            page.waitForEvent('download'),
            page.click('a:has-text("Excel")'),
        ]);

        expect(indirme.suggestedFilename()).toMatch(/\.csv$/);
    });

    test('uye etkinlik yonetimine giremez', async ({ page }) => {
        await girisYap(page, UYE.email);

        const yanit = await page.goto('/yonetim/etkinlikler');
        expect(yanit.status()).toBe(403);
    });
});
