<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(Request $request): View
    {
        $uye = $request->user();

        $etkinlikler = Event::query()
            ->yayinda()
            ->gorunur($uye)
            ->yaklasan()
            ->limit(3)
            ->get();

        $yanitlar = EventAttendee::where('user_id', $uye->id)
            ->whereIn('event_id', $etkinlikler->pluck('id'))
            ->pluck('rsvp', 'event_id');

        $profil = $uye->profile;

        return view('panel.index', [
            'uye' => $uye,
            'etkinlikler' => $etkinlikler,
            'yanitlar' => $yanitlar,
            'katildigiEtkinlik' => $uye->attendances()->where('attended', true)->count(),
            // Profilini doldurmayan uye dizinde bulunamaz; nazikce hatirlatilir
            'profilEksik' => $profil === null
                || blank($profil->services_pitch)
                || blank($profil->seeking_pitch),
            'yanitBekleyen' => $etkinlikler->filter(fn ($e) => ! isset($yanitlar[$e->id]))->count(),
        ]);
    }
}
