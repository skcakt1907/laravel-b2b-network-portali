<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\Company;
use App\Models\Event;
use App\Models\MembershipApplication;
use App\Models\Profile;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('yonetim.panel', [
            'sayilar' => $this->sayilar(),
            'bekleyenBasvurular' => MembershipApplication::where('status', 'beklemede')
                ->with('invitation.inviter')
                ->latest()
                ->limit(5)
                ->get(),
            'yaklasanEtkinlikler' => Event::query()
                ->yayinda()
                ->yaklasan()
                ->withCount([
                    'attendees as katilan_sayisi' => fn ($q) => $q->where('rsvp', 'katiliyor'),
                    'attendees as yanit_sayisi',
                ])
                ->limit(5)
                ->get(),
            'taslakEtkinlikler' => Event::where('is_published', false)
                ->where('starts_at', '>=', now())
                ->count(),
            'sonUyeler' => User::where('status', 'aktif')
                ->whereIn('role', [User::ROL_UYE, User::ROL_ADMIN])
                ->with('company')
                ->latest('approved_at')
                ->limit(5)
                ->get(),
            'ozelGunler' => $this->ozelGunler(),
            'profiliEksikSayisi' => $this->profiliEksikSayisi(),
        ]);
    }

    private function sayilar(): array
    {
        return [
            'bekleyenBasvuru' => MembershipApplication::where('status', 'beklemede')->count(),
            'aktifUye' => User::where('status', 'aktif')
                ->whereIn('role', [User::ROL_UYE, User::ROL_ADMIN])
                ->count(),
            'misafir' => User::where('role', User::ROL_MISAFIR)
                ->where('status', 'aktif')
                ->count(),
            'dondurulmus' => User::where('status', 'donduruldu')->count(),
            'yaklasanEtkinlik' => Event::yayinda()->yaklasan()->count(),
            // Dagitilan coin: yalnizca kazanimlar (harcamalar negatif oldugu icin ayri)
            'dagitilanCoin' => (int) CoinTransaction::where('amount', '>', 0)->sum('amount'),
            'dolasimdakiCoin' => (int) CoinTransaction::sum('amount'),
        ];
    }

    /**
     * Bu ay ve onumuzdeki ay kutlanacak dogum gunleri ile kurulus yildonumleri.
     *
     * Otomatik hatirlatma e-postasi Faz 8'e bagli; bu liste yoneticinin
     * elle kutlayabilmesi icin simdiden gosterilir.
     */
    private function ozelGunler(): array
    {
        $buAy = now()->month;

        $dogumGunleri = Profile::query()
            ->whereNotNull('birthday')
            ->whereMonth('birthday', $buAy)
            ->with('user:id,name')
            ->get()
            ->filter(fn ($p) => $p->user !== null)
            ->sortBy(fn ($p) => $p->birthday->day)
            ->map(fn ($p) => [
                'ad' => $p->user->name,
                'gun' => $p->birthday->format('d F'),
                'tur' => 'dogum',
            ]);

        $yildonumleri = Company::query()
            ->whereNotNull('founded_on')
            ->whereMonth('founded_on', $buAy)
            ->get()
            ->sortBy(fn ($f) => $f->founded_on->day)
            ->map(fn ($f) => [
                'ad' => $f->name,
                'gun' => $f->founded_on->format('d F'),
                'tur' => 'kurulus',
                'yil' => now()->year - $f->founded_on->year,
            ]);

        return $dogumGunleri->concat($yildonumleri)->values()->all();
    }

    /** Asansor cumlesini doldurmamis uyeler: dizinin degerini dusuruyorlar. */
    private function profiliEksikSayisi(): int
    {
        return User::where('status', 'aktif')
            ->where('role', User::ROL_UYE)
            ->where(function ($q) {
                $q->whereDoesntHave('profile')
                    ->orWhereHas('profile', fn ($p) => $p->whereNull('services_pitch')
                        ->orWhere('services_pitch', ''));
            })
            ->count();
    }
}
