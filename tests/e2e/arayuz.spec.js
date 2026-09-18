import { test, expect } from '@playwright/test';

const AKTIF = { email: 'e2e-aktif@dnunity.test', password: 'test12345' };

async function girisYap(page) {
    await page.goto('/giris');
    await page.fill('input[name="email"]', AKTIF.email);
    await page.fill('input[name="password"]', AKTIF.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Panel arayuzu', () => {
    test('sayfa konsol hatasi uretmez', async ({ page }) => {
        const hatalar = [];
        page.on('console', (m) => m.type() === 'error' && hatalar.push(m.text()));
        page.on('pageerror', (e) => hatalar.push(e.message));

        await girisYap(page);
        await expect(page.locator('h1')).toBeVisible();

        expect(hatalar, 'konsol hatalari').toEqual([]);
    });

    test('yatay kaydirma olusmaz', async ({ page }) => {
        await girisYap(page);

        const tasma = await page.evaluate(
            () => document.documentElement.scrollWidth > document.documentElement.clientWidth
        );
        expect(tasma, 'sayfa yatayda tasiyor').toBe(false);
    });

    test('yan menu belge yuksekligini kaplar', async ({ page, isMobile }) => {
        test.skip(isMobile, 'Mobilde yan menu gizli acilir, bu kontrol masaustu icindir');

        await girisYap(page);

        const { menu, belge } = await page.evaluate(() => ({
            menu: Math.round(document.getElementById('sidebar').getBoundingClientRect().height),
            belge: document.documentElement.scrollHeight,
        }));

        // Yuvarlamadan kaynakli 1-2 piksel sapmaya izin ver
        expect(Math.abs(menu - belge)).toBeLessThanOrEqual(2);
    });
});

test.describe('Mobil menu', () => {
    test('mobilde menu gizli baslar, dugmeyle acilir', async ({ page, isMobile }) => {
        test.skip(!isMobile, 'Yalnizca mobil projede anlamli');

        await girisYap(page);

        // Baslangicta ekran disinda
        const baslangic = await page.evaluate(
            () => document.getElementById('sidebar').getBoundingClientRect().left
        );
        expect(baslangic).toBeLessThan(0);

        await page.click('#sidebarToggle');

        // Gecis animasyonu bitene kadar bekle
        await expect
            .poll(async () =>
                page.evaluate(() =>
                    Math.round(document.getElementById('sidebar').getBoundingClientRect().left)
                )
            )
            .toBe(0);

        await expect(page.locator('#sidebarBackdrop')).toBeVisible();

        // Perdeye tiklayinca kapanmali. Perde tum ekrani kapladigi icin
        // merkezi yan menunun ALTINDA kalir; menunun sagina tiklanmali.
        await page.click('#sidebarBackdrop', { position: { x: 350, y: 400 } });
        await expect
            .poll(async () =>
                page.evaluate(() =>
                    document.getElementById('sidebar').getBoundingClientRect().left
                )
            )
            .toBeLessThan(0);
    });
});
