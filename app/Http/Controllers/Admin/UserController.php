<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MembershipTermination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class UserController extends Controller
{
    public function __construct(private readonly MembershipTermination $surec) {}

    public function index(Request $request): View
    {
        $durum = $request->query('durum', 'hepsi');
        $arama = trim((string) $request->query('q', ''));

        $uyeler = User::query()
            ->with('company')
            ->when(
                in_array($durum, ['aktif', 'donduruldu', 'pasif', 'beklemede'], true),
                fn ($q) => $q->where('status', $durum)
            )
            ->when($arama !== '', function ($q) use ($arama) {
                $q->where(function ($alt) use ($arama) {
                    $alt->where('name', 'like', "%{$arama}%")
                        ->orWhere('email', 'like', "%{$arama}%");
                });
            })
            // Onay bekleyenler en uste. CASE kullaniliyor cunku MySQL'e ozgu
            // FIELD() testlerdeki SQLite'ta calismaz.
            ->orderByRaw("CASE status
                WHEN 'beklemede' THEN 1
                WHEN 'aktif' THEN 2
                WHEN 'donduruldu' THEN 3
                ELSE 4 END")
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('yonetim.uyeler.index', [
            'uyeler' => $uyeler,
            'durum' => $durum,
            'arama' => $arama,
        ]);
    }

    public function show(User $uye): View
    {
        $uye->load(['company', 'profile', 'approvedBy', 'invitedBy']);

        return view('yonetim.uyeler.show', [
            'uye' => $uye,
            'coinBakiye' => $uye->coinBalance(),
            'katilimSayisi' => $uye->attendances()->where('attended', true)->count(),
        ]);
    }

    public function durum(User $uye, Request $request): RedirectResponse
    {
        $veri = $request->validate([
            'islem' => ['required', 'in:dondur,aktiflestir,pasifeCek'],
        ]);

        try {
            match ($veri['islem']) {
                'dondur' => $this->surec->dondur($uye),
                'aktiflestir' => $this->surec->aktiflestir($uye),
                'pasifeCek' => $this->surec->pasifeCek($uye),
            };
        } catch (RuntimeException $e) {
            return back()->with('hata', $e->getMessage());
        }

        $mesaj = [
            'dondur' => 'Uyelik donduruldu. Acik oturumu varsa bir sonraki istekte kapanir.',
            'aktiflestir' => 'Uyelik yeniden aktiflestirildi.',
            'pasifeCek' => 'Uyelik pasife alindi.',
        ][$veri['islem']];

        return back()->with('basari', $mesaj);
    }

    public function anonimlestir(User $uye, Request $request): RedirectResponse
    {
        // Yanlislikla silmeyi zorlastirmak icin ad soyad birebir yazilmali
        $request->validate([
            'onay_adi' => ['required', 'string'],
        ], [], ['onay_adi' => 'onay metni']);

        if (trim($request->input('onay_adi')) !== $uye->name) {
            return back()->with('hata', 'Onay icin uyenin adini birebir yazmalisiniz.');
        }

        try {
            $this->surec->anonimlestir($uye);
        } catch (RuntimeException $e) {
            return back()->with('hata', $e->getMessage());
        }

        return redirect()
            ->route('yonetim.uyeler.index')
            ->with('basari', 'Uyenin kisisel verileri silindi. Islem geri alinamaz.');
    }
}
