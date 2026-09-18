<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Uye dizini - platformun ana faydasi.
 *
 * Dizinde yalnizca aktif, listelenmeye acik uyeler gorunur.
 * Misafirler bu bolume hic giremez ('uye' middleware'i ile kapali).
 */
class DirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $arama = trim((string) $request->query('q', ''));
        $sektor = trim((string) $request->query('sektor', ''));
        $sehir = trim((string) $request->query('sehir', ''));
        $marka = (string) $request->query('marka', '');

        $uyeler = User::query()
            ->with(['company', 'profile'])
            ->where('status', 'aktif')
            ->whereIn('role', [User::ROL_UYE, User::ROL_ADMIN])
            // Dizinde gorunmek istemeyenler listelenmez
            ->whereHas('profile', fn ($q) => $q->where('is_listed', true))
            ->when($arama !== '', function ($q) use ($arama) {
                $q->where(function ($alt) use ($arama) {
                    $alt->where('name', 'like', "%{$arama}%")
                        ->orWhere('title', 'like', "%{$arama}%")
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$arama}%"))
                        // Asansor cumlesinde de aranir: "web tasarim" arayan bulsun
                        ->orWhereHas('profile', fn ($p) => $p
                            ->where('services_pitch', 'like', "%{$arama}%")
                            ->orWhere('seeking_pitch', 'like', "%{$arama}%"));
                });
            })
            ->when($sektor !== '', fn ($q) => $q->whereHas('company', fn ($c) => $c->where('sector', $sektor)))
            ->when($sehir !== '', fn ($q) => $q->whereHas('company', fn ($c) => $c->where('city', $sehir)))
            ->when(
                array_key_exists($marka, Profile::MARKA_ETIKETLERI),
                fn ($q) => $q->whereHas('profile', function ($p) use ($marka) {
                    // "ikisi" secen uye her iki marka filtresinde de cikar
                    $marka === 'ikisi'
                        ? $p->where('brand', 'ikisi')
                        : $p->whereIn('brand', [$marka, 'ikisi']);
                })
            )
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('dizin.index', [
            'uyeler' => $uyeler,
            'arama' => $arama,
            'sektor' => $sektor,
            'sehir' => $sehir,
            'marka' => $marka,
            'sektorler' => $this->secenekler('sector'),
            'sehirler' => $this->secenekler('city'),
            'markalar' => Profile::MARKA_ETIKETLERI,
            'filtreVar' => $arama !== '' || $sektor !== '' || $sehir !== '' || $marka !== '',
        ]);
    }

    public function show(User $uye): View
    {
        // Gorme hakki UserPolicy'de: misafir goremez, listelenmeyen profil gizlidir
        $this->authorize('view', $uye);

        $uye->load(['company', 'profile']);

        return view('dizin.profil', ['uye' => $uye]);
    }

    /** Filtre acilir listeleri gercekte kullanilan degerlerden uretilir. */
    private function secenekler(string $sutun): array
    {
        return Company::query()
            ->whereNotNull($sutun)
            ->where($sutun, '!=', '')
            ->distinct()
            ->orderBy($sutun)
            ->pluck($sutun)
            ->all();
    }
}
