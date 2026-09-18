<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipApplication;
use App\Services\MembershipApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ApplicationController extends Controller
{
    public function __construct(private readonly MembershipApproval $onaySurec) {}

    public function index(Request $request): View
    {
        $durum = $request->query('durum', 'beklemede');

        if (! in_array($durum, ['beklemede', 'onaylandi', 'reddedildi', 'hepsi'], true)) {
            $durum = 'beklemede';
        }

        $basvurular = MembershipApplication::query()
            ->with(['invitation.inviter', 'reviewer'])
            ->when($durum !== 'hepsi', fn ($q) => $q->where('status', $durum))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('yonetim.basvurular.index', [
            'basvurular' => $basvurular,
            'durum' => $durum,
            'bekleyenSayisi' => MembershipApplication::where('status', 'beklemede')->count(),
        ]);
    }

    public function show(MembershipApplication $basvuru): View
    {
        $basvuru->load(['invitation.inviter', 'reviewer', 'createdUser']);

        return view('yonetim.basvurular.show', ['basvuru' => $basvuru]);
    }

    public function approve(MembershipApplication $basvuru, Request $request): RedirectResponse
    {
        try {
            $sonuc = $this->onaySurec->onayla($basvuru, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('hata', $e->getMessage());
        }

        return redirect()
            ->route('yonetim.basvurular.show', $basvuru)
            ->with('basari', $sonuc['user']->name.' uye olarak eklendi. Gecici sifre e-posta ile gonderildi.');
    }

    public function reject(MembershipApplication $basvuru, Request $request): RedirectResponse
    {
        $veri = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ], [], ['admin_note' => 'not']);

        try {
            $this->onaySurec->reddet($basvuru, $request->user(), $veri['admin_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('hata', $e->getMessage());
        }

        return redirect()
            ->route('yonetim.basvurular.show', $basvuru)
            ->with('bilgi', 'Basvuru reddedildi olarak isaretlendi.');
    }
}
