import { test, expect } from '@playwright/test';

const ADMIN = { email: 'admin@dnunity.com', password: 'admin123' };
const UYE = { email: 'e2e-aktif@dnunity.test', password: 'test12345' };
const MISAFIR = { email: 'e2e-misafir@dnunity.test', password: 'test12345' };

async function girisYap(page, eposta, sifre = 'test12345') {
    await page.context().clearCookies();
    await page.goto('/giris');
    await page.fill('input[name="email"]', eposta);
    await page.fill('input[name="password"]', sifre);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Yonetim paneli', () => {
    test('ozet sayilari ve bolumler yuklenir', async ({ page }) => {
        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim');

        await expect(page.locator('h1')).toContainText('Yonetim paneli');
        await expect(page.locator('body')).toContainText('Aktif uye');
        await expect(page.locator('body')).toContainText('Bekleyen basvuru');
        await expect(page.locator('body')).toContainText('Yaklasan etkinlikler');
        await expect(page.locator('body')).toContainText('Topluluk sagligi');
    });

    test('bekleyen basvuru varsa uyari ve kisayol cikar', async ({ page }) => {
        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim');

        const uyari = page.locator('.alert-warn', { hasText: 'incelenmeyi bekliyor' });

        // Bekleyen basvuru olabilir de olmayabilir de; varsa kisayol calismali
        if (await uyari.count()) {
            await uyari.getByRole('link', { name: 'Incele' }).click();
            await page.waitForLoadState('networkidle');
            await expect(page).toHaveURL(/yonetim\/basvurular/);
        }
    });

    test('menudeki yonetim paneli bagi calisir', async ({ page, isMobile }) => {
        await girisYap(page, ADMIN.email, ADMIN.password);

        // Mobilde yan menu ekran disinda baslar; once acilmali
        if (isMobile) {
            await page.click('#sidebarToggle');
            await expect
                .poll(async () => page.evaluate(
                    () => Math.round(document.getElementById('sidebar').getBoundingClientRect().left)
                ))
                .toBe(0);
        }

        await page.click('.sidebar a:has-text("Yonetim Paneli")');
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/\/yonetim$/);
    });

    test('uye yonetim paneline giremez', async ({ page }) => {
        await girisYap(page, UYE.email);

        const yanit = await page.goto('/yonetim');
        expect(yanit.status()).toBe(403);
    });

    test('konsol hatasi uretmez', async ({ page }) => {
        const hatalar = [];
        page.on('console', (m) => m.type() === 'error' && hatalar.push(m.text()));
        page.on('pageerror', (e) => hatalar.push(e.message));

        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim');
        await expect(page.locator('h1')).toBeVisible();

        expect(hatalar).toEqual([]);
    });
});

test.describe('Uye paneli', () => {
    test('yaklasan etkinlikler ve kisayollar gorunur', async ({ page }) => {
        await girisYap(page, UYE.email);

        await expect(page.locator('h1')).toContainText('Panel');
        await expect(page.locator('body')).toContainText('Yaklasan etkinlikler');
        await expect(page.locator('a:has-text("Uye dizininde ara")')).toBeVisible();
    });

    test('misafirde coin ve dizin kisayolu yok', async ({ page }) => {
        await girisYap(page, MISAFIR.email);

        await expect(page.locator('body')).not.toContainText('Unitycoin bakiyeniz');
        await expect(page.locator('a:has-text("Uye dizininde ara")')).toHaveCount(0);
        // Etkinlik takvimi misafire de acik
        await expect(page.locator('a:has-text("Etkinlik takvimi")')).toBeVisible();
    });

    test('yatay kaydirma olusmaz', async ({ page }) => {
        await girisYap(page, ADMIN.email, ADMIN.password);
        await page.goto('/yonetim');

        const tasma = await page.evaluate(
            () => document.documentElement.scrollWidth > document.documentElement.clientWidth
        );
        expect(tasma, 'yonetim paneli yatayda tasiyor').toBe(false);
    });
});
