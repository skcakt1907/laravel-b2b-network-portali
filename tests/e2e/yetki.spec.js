import { test, expect } from '@playwright/test';

const SIFRE = 'test12345';

const ADMIN = { email: 'admin@dnunity.com', password: 'admin123' };
const UYE = { email: 'e2e-aktif@dnunity.test', password: SIFRE };
const MISAFIR = { email: 'e2e-misafir@dnunity.test', password: SIFRE };

const YONETIM_ADRESLERI = [
    '/yonetim',
    '/yonetim/basvurular',
    '/yonetim/uyeler',
    '/yonetim/etkinlikler',
    '/yonetim/coin',
];

async function girisYap(page, { email, password }) {
    await page.goto('/giris');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('Yonetim alani', () => {
    test('admin tum yonetim sayfalarini acabilir', async ({ page }) => {
        await girisYap(page, ADMIN);

        for (const adres of YONETIM_ADRESLERI) {
            const yanit = await page.goto(adres);
            expect(yanit.status(), `${adres} durumu`).toBe(200);
        }
    });

    test('uye adres cubugundan yonetime giremez', async ({ page }) => {
        await girisYap(page, UYE);

        for (const adres of YONETIM_ADRESLERI) {
            const yanit = await page.goto(adres);
            expect(yanit.status(), `${adres} uyeye acik kalmis`).toBe(403);
        }
    });

    test('misafir yonetime giremez', async ({ page }) => {
        await girisYap(page, MISAFIR);

        const yanit = await page.goto('/yonetim/coin');
        expect(yanit.status()).toBe(403);
    });

    test('403 sayfasi Turkce ve yonlendirmeli', async ({ page }) => {
        await girisYap(page, UYE);
        await page.goto('/yonetim');

        await expect(page.locator('body')).toContainText('erisim yetkiniz yok');
        await expect(page.locator('a.btn')).toContainText('Panele don');
    });
});

test.describe('Uyeye ozel alanlar', () => {
    test('misafir uye dizinine ve cuzdana giremez', async ({ page }) => {
        await girisYap(page, MISAFIR);

        for (const adres of ['/uyeler', '/cuzdan']) {
            const yanit = await page.goto(adres);
            expect(yanit.status(), `${adres} misafire acik kalmis`).toBe(403);
        }
    });

    test('uye dizine ve cuzdana girebilir', async ({ page }) => {
        await girisYap(page, UYE);

        for (const adres of ['/uyeler', '/cuzdan']) {
            const yanit = await page.goto(adres);
            expect(yanit.status(), `${adres} durumu`).toBe(200);
        }
    });

    test('misafir etkinlikleri ve kendi profilini gorebilir', async ({ page }) => {
        await girisYap(page, MISAFIR);

        for (const adres of ['/etkinlikler', '/profilim']) {
            const yanit = await page.goto(adres);
            expect(yanit.status(), `${adres} durumu`).toBe(200);
        }
    });
});

test.describe('Menu gorunurlugu role gore degisir', () => {
    test('misafir menusunde dizin, cuzdan ve yonetim yok', async ({ page }) => {
        await girisYap(page, MISAFIR);

        const menu = page.locator('.sidebar');
        await expect(menu).toContainText('Etkinlikler');
        await expect(menu).toContainText('Profilim');
        await expect(menu).not.toContainText('Uye Dizini');
        await expect(menu).not.toContainText('Unitycoin');
        await expect(menu).not.toContainText('Basvurular');

        // Ust bardaki coin rozeti ve paneldeki bakiye karti da misafire gosterilmez
        await expect(page.locator('.topbar .badge-gold')).toHaveCount(0);
        await expect(page.locator('.panel-body')).not.toContainText('Unitycoin bakiyeniz');
    });

    test('uye menusunde dizin ve cuzdan var, yonetim yok', async ({ page }) => {
        await girisYap(page, UYE);

        const menu = page.locator('.sidebar');
        await expect(menu).toContainText('Uye Dizini');
        await expect(menu).toContainText('Unitycoin');
        await expect(menu).not.toContainText('Basvurular');
    });

    test('admin menusunde yonetim bolumu var', async ({ page }) => {
        await girisYap(page, ADMIN);

        const menu = page.locator('.sidebar');
        await expect(menu).toContainText('Basvurular');
        await expect(menu).toContainText('Coin Yonetimi');
    });
});
