<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\PresentationOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    public function __construct(private readonly PresentationOrder $sunumSirasi) {}

    public function index(Request $request): View
    {
        $gecmis = $request->boolean('gecmis');

        return view('yonetim.etkinlikler.index', [
            'etkinlikler' => Event::query()
                ->withCount(['attendees as katilan_sayisi' => fn ($q) => $q->where('rsvp', 'katiliyor')])
                ->when($gecmis, fn ($q) => $q->gecmis(), fn ($q) => $q->orderBy('starts_at'))
                ->when(! $gecmis, fn ($q) => $q->where('starts_at', '>=', now()->subDay()))
                ->paginate(20)
                ->withQueryString(),
            'gecmis' => $gecmis,
        ]);
    }

    public function create(): View
    {
        return view('yonetim.etkinlikler.form', ['etkinlik' => new Event()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $veri = $this->dogrula($request);

        $etkinlik = new Event();
        $etkinlik->fill($veri);
        $etkinlik->slug = $this->benzersizSlug($veri['title']);
        $etkinlik->created_by = $request->user()->id;
        $etkinlik->save();

        return redirect()
            ->route('yonetim.etkinlikler.duzenle', $etkinlik)
            ->with('basari', 'Etkinlik olusturuldu.');
    }

    public function edit(Event $etkinlik): View
    {
        $etkinlik->load(['attendees.user.company']);

        return view('yonetim.etkinlikler.form', [
            'etkinlik' => $etkinlik,
            'katilimcilar' => $etkinlik->attendees
                ->sortBy(fn ($k) => $k->presentation_order ?? PHP_INT_MAX),
        ]);
    }

    public function update(Request $request, Event $etkinlik): RedirectResponse
    {
        $veri = $this->dogrula($request, $etkinlik);

        $etkinlik->fill($veri);

        if ($etkinlik->isDirty('title')) {
            $etkinlik->slug = $this->benzersizSlug($veri['title'], $etkinlik->id);
        }

        $etkinlik->save();

        return back()->with('basari', 'Etkinlik guncellendi.');
    }

    public function destroy(Event $etkinlik): RedirectResponse
    {
        if ($etkinlik->attendees()->exists()) {
            return back()->with(
                'hata',
                'Katilim yaniti verilmis etkinlik silinemez. Yayindan kaldirabilirsiniz.'
            );
        }

        $etkinlik->delete();

        return redirect()->route('yonetim.etkinlikler.index')
            ->with('bilgi', 'Etkinlik silindi.');
    }

    // ------------------------------------------------------------ sunum sirasi

    public function sirala(Event $etkinlik): RedirectResponse
    {
        $adet = $this->sunumSirasi->uret($etkinlik);

        return back()->with('basari', $adet.' katilimci icin sunum sirasi olusturuldu.');
    }

    public function siraKaydet(Request $request, Event $etkinlik): RedirectResponse
    {
        $request->validate([
            'sira' => ['array'],
            'sira.*' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $this->sunumSirasi->elleAyarla($etkinlik, $request->input('sira', []));

        return back()->with('basari', 'Sunum sirasi kaydedildi.');
    }

    // ------------------------------------------------------------ yoklama

    public function yoklama(Request $request, Event $etkinlik): RedirectResponse
    {
        $katilanlar = collect($request->input('katildi', []))->map(fn ($v) => (int) $v);

        $etkinlik->attendees()->update(['attended' => false]);

        if ($katilanlar->isNotEmpty()) {
            $etkinlik->attendees()->whereIn('id', $katilanlar)->update(['attended' => true]);
        }

        return back()->with('basari', 'Yoklama kaydedildi.');
    }

    // ------------------------------------------------------------ disa aktarim

    /**
     * Katilimci listesini CSV olarak indirir.
     *
     * UTF-8 BOM ve noktali virgul ayraci kullanilir: Turkce Windows Excel
     * dosyayi bu sekilde dogru acar, aksi halde harfler bozulur ve tum
     * satir tek hucreye duser.
     */
    public function disaAktar(Event $etkinlik): StreamedResponse
    {
        $etkinlik->load(['attendees.user.company']);

        $satirlar = $etkinlik->attendees
            ->sortBy(fn ($k) => $k->presentation_order ?? PHP_INT_MAX);

        $dosyaAdi = Str::slug($etkinlik->title).'-katilimcilar.csv';

        return response()->streamDownload(function () use ($satirlar) {
            $cikti = fopen('php://output', 'w');

            fwrite($cikti, "\xEF\xBB\xBF");

            fputcsv($cikti, [
                'Sunum Sirasi', 'Ad Soyad', 'Firma', 'E-posta', 'Telefon',
                'Katilim', 'Yoklama', 'Yanit Tarihi',
            ], ';');

            foreach ($satirlar as $k) {
                fputcsv($cikti, [
                    $k->presentation_order ?? '',
                    $k->user->name,
                    $k->user->company?->name ?? '',
                    $k->user->email,
                    $k->user->phone ?? '',
                    ['katiliyor' => 'Katiliyor', 'katilmiyor' => 'Katilmiyor', 'yanitsiz' => 'Yanitsiz'][$k->rsvp],
                    $k->attended ? 'Geldi' : '',
                    $k->responded_at?->format('d.m.Y H:i') ?? '',
                ], ';');
            }

            fclose($cikti);
        }, $dosyaAdi, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------ ic isler

    private function dogrula(Request $request, ?Event $etkinlik = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['online', 'fiziksel'])],
            'category' => ['required', Rule::in(['toplanti', 'egitim', 'gezi', 'diger'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
            'online_url' => ['nullable', 'url', 'max:300'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'visibility' => ['required', Rule::in(['uye', 'uye_misafir'])],
            'is_published' => ['nullable', 'boolean'],
            'has_presentations' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'baslik',
            'starts_at' => 'baslangic zamani',
            'ends_at' => 'bitis zamani',
            'capacity' => 'kontenjan',
            'online_url' => 'toplanti bagi',
        ]) + [
            // Isaretlenmemis kutular istekte hic gelmez; acikca false yazilir
            'is_published' => $request->boolean('is_published'),
            'has_presentations' => $request->boolean('has_presentations'),
        ];
    }

    private function benzersizSlug(string $baslik, ?int $haricId = null): string
    {
        $kok = Str::slug($baslik) ?: 'etkinlik';
        $slug = $kok;
        $sayac = 2;

        while (Event::where('slug', $slug)
            ->when($haricId, fn ($q) => $q->where('id', '!=', $haricId))
            ->exists()) {
            $slug = $kok.'-'.$sayac++;
        }

        return $slug;
    }
}
