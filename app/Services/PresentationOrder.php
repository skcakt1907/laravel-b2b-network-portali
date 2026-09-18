<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Support\Facades\DB;

/**
 * Toplantidaki 3 dakikalik sunum sirasi.
 *
 * Sira KATILIYORUM diyenler arasinda RASTGELE dagitilir; katilim onayi
 * sirasina gore verilmez. Sebebi: sabit bir olcut kullanilirsa her ay
 * ayni kisiler basa/sona duser. Rastgele dagitim her toplantida herkese
 * esit sans verir.
 */
class PresentationOrder
{
    /**
     * Sirayi bastan uretir.
     *
     * @return int Sira verilen katilimci sayisi
     */
    public function uret(Event $etkinlik): int
    {
        return DB::transaction(function () use ($etkinlik) {
            // Once herkesin sirasi silinir: katilmaktan vazgecenler kalmasin
            EventAttendee::where('event_id', $etkinlik->id)
                ->update(['presentation_order' => null]);

            $katilanlar = EventAttendee::where('event_id', $etkinlik->id)
                ->where('rsvp', 'katiliyor')
                ->pluck('id')
                ->shuffle();

            foreach ($katilanlar as $sira => $id) {
                EventAttendee::where('id', $id)
                    ->update(['presentation_order' => $sira + 1]);
            }

            return $katilanlar->count();
        });
    }

    /**
     * Admin sirayi elle degistirir.
     *
     * @param  array<int,int|null>  $siralar  katilimci id => sira
     */
    public function elleAyarla(Event $etkinlik, array $siralar): void
    {
        DB::transaction(function () use ($etkinlik, $siralar) {
            foreach ($siralar as $katilimciId => $sira) {
                EventAttendee::where('event_id', $etkinlik->id)
                    ->where('id', (int) $katilimciId)
                    ->update([
                        'presentation_order' => ($sira === null || $sira === '')
                            ? null
                            : max(1, (int) $sira),
                    ]);
            }
        });
    }

    /** Sirayi temizler (ornegin toplanti ertelendiginde). */
    public function temizle(Event $etkinlik): void
    {
        EventAttendee::where('event_id', $etkinlik->id)
            ->update(['presentation_order' => null]);
    }
}
