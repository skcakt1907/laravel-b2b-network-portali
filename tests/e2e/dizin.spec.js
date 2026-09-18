import { test, expect } from '@playwright/test';

/** Profil duzenleme durum uretir; her proje kendi hesabini kullanir. */
const profilHesabi = (proje) => `e2e-profil-${proje}@dnunity.test`;

const UYE = { email: 'e2e-aktif@dnunity.test', password: 'test12345' };

async function girisYap(page, eposta, sifre = 'test12345') {
    await page.goto('/giris');
    await page.fill('input[name="email"]', eposta);
    await page.fill('input[name="password"]', sifre);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Uye dizini', () => {
    test('dizin acilir ve filtre secenekleri dolu gelir', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/uyeler');

        await expect(page.locator('h1')).toContainText('Uye dizini');
        await expect(page.locator('select[name="sektor"] option')).not.toHaveCount(1);
        await expect(page.locator('select[name="sehir"] option')).not.toHaveCount(1);
    });

    test('isme gore arama calisir', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/uyeler');

        await page.fill('input[name="q"]', 'Selin');
        await page.click('button:has-text("Ara")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('Selin Aydin');
        await expect(page.locator('body')).not.toContainText('Kerem Dogan');
    });

    test('hizmete gore arama calisir (asansor cumlesi taranir)', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/uyeler?q=' + encodeURIComponent('yat kiralama'));

        await expect(page.locator('body')).toContainText('Kerem Dogan');
        await expect(page.locator('body')).not.toContainText('Ayse Kocak');
    });

    test('sektor filtresi calisir', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/uyeler?sektor=Turizm');

        await expect(page.locator('body')).toContainText('Kerem Dogan');
        await expect(page.locator('body')).not.toContainText('Burak Yildiz');
    });

    test('sonuc bulunamayinca temizleme secenegi sunulur', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/uyeler?q=boylebirseyyok12345');

        await expect(page.locator('body')).toContainText('bulunamadi');
        await expect(page.locator('a:has-text("Filtreleri temizle")').first()).toBeVisible();
    });

    test('kartlardan hizli iletisim baglari uretilir', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/uyeler?q=Selin');

        // WhatsApp bagi ulke koduyla normallestirilmis olmali
        const wa = page.locator('a[href^="https://wa.me/"]').first();
        await expect(wa).toBeVisible();
        await expect(wa).toHaveAttribute('href', /wa\.me\/90\d{10}/);

        await expect(page.locator('a[href^="mailto:"]').first()).toBeVisible();
    });

    test('profil sayfasi asansor cumlesini gosterir', async ({ page }) => {
        await girisYap(page, UYE.email);
        await page.goto('/uyeler?q=Selin');
        await page.click('a:has-text("Selin Aydin")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('h1')).toContainText('Selin Aydin');
        // Basliklar ekranda buyuk harf gorunur ama bu CSS ile yapilir;
        // DOM'daki metin normal yazimdir.
        await expect(page.locator('body')).toContainText('Hizmetlerimiz');
        await expect(page.locator('body')).toContainText('Arayislarimiz');
        await expect(page.locator('body')).toContainText('Mavi Reklam Ajansi');
    });

    test('misafir dizine giremez', async ({ page }) => {
        await girisYap(page, 'e2e-misafir@dnunity.test');

        const yanit = await page.goto('/uyeler');
        expect(yanit.status()).toBe(403);
    });
});

test.describe('Profil duzenleme', () => {
    test('uye asansor cumlesini kaydedebilir ve dizinde gorunur', async ({ page }, testInfo) => {
        const proje = testInfo.project.name;
        const eposta = profilHesabi(proje);
        const hizmet = `E2E ozel hizmet metni ${proje}`;

        await girisYap(page, eposta);
        await page.goto('/profilim');

        await page.fill('#services_pitch', hizmet);
        await page.fill('#seeking_pitch', 'E2E arayis metni.');
        await page.selectOption('select[name="brand"]', 'tatilimsensin');
        await page.check('#is_listed');
        await page.click('button:has-text("Degisiklikleri kaydet")');
        await page.waitForLoadState('networkidle');

        await expect(page.locator('body')).toContainText('Profiliniz guncellendi');

        // Kaydedilen metin dizin aramasinda bulunabilmeli
        await page.goto('/uyeler?q=' + encodeURIComponent(hizmet));
        await expect(page.locator('body')).toContainText('Tatilim Sensin');
    });

    test('karakter sayaci yazdikca guncellenir', async ({ page }, testInfo) => {
        await girisYap(page, profilHesabi(testInfo.project.name));
        await page.goto('/profilim');

        await page.fill('#services_pitch', 'On karakter');
        await expect(page.locator('#sayac1')).toHaveText('11');
    });

    test('dizinde gorunmeyi kapatan uye listelenmez', async ({ page }, testInfo) => {
        const proje = testInfo.project.name;
        const eposta = profilHesabi(proje);

        await girisYap(page, eposta);
        await page.goto('/profilim');
        await page.fill('#name', `E2E Profil Uyesi ${proje}`);
        await page.uncheck('#is_listed');
        await page.click('button:has-text("Degisiklikleri kaydet")');
        await page.waitForLoadState('networkidle');

        await page.goto('/uyeler?q=' + encodeURIComponent(`E2E Profil Uyesi ${proje}`));
        await expect(page.locator('body')).toContainText('bulunamadi');

        // Kendi profilini yine de gorebilmeli
        await page.goto('/profilim');
        await expect(page.locator('#is_listed')).not.toBeChecked();
    });
});
