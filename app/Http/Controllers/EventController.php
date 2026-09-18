<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Uye tarafi: etkinlik takvimi, detay ve katilim onayi.
 *
 * Misafirler yalnizca kendilerine acilmis (uye_misafir) etkinlikleri gorur;
 * suzme EventPolicy ve Event::gorunur() kapsaminda yapilir.
 */
class EventController extends Controller
{
    public function index(Request $request): View
    {
        $kullanici = $request->user();
        $gecmis = $request->boolean('gecmis');

        $etkinlikler = Event::query()
            ->yayinda()
            ->gorunur($kullanici)
            ->when($gecmis, fn ($q) => $q->gecmis(), fn ($q) => $q->yaklasan())
            ->withCount(['attendees as katilan_sayisi' => fn ($q) => $q->where('rsvp', 'katiliyor')])
            ->paginate(10)
            ->withQueryString();

        // Uyenin kendi yanitlari: her kartta "katiliyorum" rozetini gostermek icin
        $yanitlar = EventAttendee::where('user_id', $kullanici->id)
            ->whereIn('event_id', $etkinlikler->pluck('id'))
            ->pluck('rsvp', 'event_id');

        return view('etkinlik.index', [
            'etkinlikler' => $etkinlikler,
            'yanitlar' => $yanitlar,
            'gecmis' => $gecmis,
        ]);
    }

    public function show(Request $request, Event $etkinlik): View
    {
        $this->authorize('view', $etkinlik);

        $kullanici = $request->user();

        $katilim = EventAttendee::where('event_id', $etkinlik->id)
            ->where('user_id', $kullanici->id)
            ->first();

        return view('etkinlik.detay', [
            'etkinlik' => $etkinlik,
            'katilim' => $katilim,
            'katilanSayisi' => $etkinlik->katilanSayisi(),
            // Katilimci listesi yalnizca uyelere gosterilir
            'katilimcilar' => $kullanici->isMisafir()
                ? collect()
                : $etkinlik->attendees()
                    ->where('rsvp', 'katiliyor')
                    ->with('user.company')
                    ->get()
                    ->sortBy(fn ($k) => $k->presentation_order ?? PHP_INT_MAX),
        ]);
    }

    public function rsvp(Request $request, Event $etkinlik): RedirectResponse
    {
        $this->authorize('rsvp', $etkinlik);

        $veri = $request->validate([
            'rsvp' => ['required', Rule::in(['katiliyor', 'katilmiyor'])],
        ]);

        $kullanici = $request->user();

        $katilim = EventAttendee::firstOrNew([
            'event_id' => $etkinlik->id,
            'user_id' => $kullanici->id,
        ]);

        // Kontenjan denetimi: yalnizca YENI katilim eklerken bakilir,
        // zaten katiliyor olan biri yanitini yenilerken engellenmemeli
        if ($veri['rsvp'] === 'katiliyor'
            && $katilim->rsvp !== 'katiliyor'
            && $etkinlik->kontenjanDoluMu()) {
            return back()->with('hata', 'Bu etkinligin kontenjani dolmus.');
        }

        $katilim->event_id = $etkinlik->id;
        $katilim->user_id = $kullanici->id;
        $katilim->rsvp = $veri['rsvp'];
        $katilim->responded_at = now();

        // Katilmaktan vazgecen kisinin sunum sirasi dusmelidir
        if ($veri['rsvp'] === 'katilmiyor') {
            $katilim->presentation_order = null;
        }

        $katilim->save();

        return back()->with('basari', $veri['rsvp'] === 'katiliyor'
            ? 'Katiliminiz kaydedildi. Gorusmek uzere!'
            : 'Katilamayacaginizi bildirdiniz.');
    }
}
