@extends('layouts.panel')

@section('title', 'Tasarim Sistemi')
@section('subtitle', 'Faz 1 - bilesen on izlemesi. Bu sayfa yayina cikmadan once silinecek.')

@section('content')

    {{-- ----------------------------------------------------------- renkler --}}
    <div class="card-ui mb-4">
        <div class="card-ui-head"><h2>Renk degiskenleri</h2></div>
        <div class="card-ui-body">
            <div class="row g-3">
                @foreach ([
                    'Lacivert (ana)' => 'var(--nav)',
                    'Lacivert koyu' => 'var(--nav-700)',
                    'Lacivert orta' => 'var(--nav-500)',
                    'Lacivert acik' => 'var(--nav-100)',
                    'Amber (vurgu)' => 'var(--gold)',
                    'Amber koyu' => 'var(--gold-700)',
                    'Zemin' => 'var(--bg)',
                    'Kenarlik' => 'var(--line)',
                ] as $ad => $deger)
                    <div class="col-6 col-md-3">
                        <div style="height:56px;border-radius:8px;border:1px solid var(--line);background:{{ $deger }}"></div>
                        <div style="font-size:.8125rem;margin-top:.35rem">{{ $ad }}</div>
                        <div class="text-muted-2" style="font-size:.75rem">{{ $deger }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ----------------------------------------------------------- istatistik --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Aktif uye</div>
                <div class="stat-value">48</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Bekleyen basvuru</div>
                <div class="stat-value">3</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Yaklasan etkinlik</div>
                <div class="stat-value">2</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat">
                <div class="stat-label">Dagitilan coin</div>
                <div class="stat-value">1.240 <small>UC</small></div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ------------------------------------------------------- butonlar --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Butonlar</h2></div>
                <div class="card-ui-body d-flex flex-wrap gap-2">
                    <button class="btn btn-primary">Birincil</button>
                    <button class="btn btn-gold">Vurgu</button>
                    <button class="btn btn-outline">Cerceveli</button>
                    <button class="btn btn-ghost">Sade</button>
                    <button class="btn btn-primary btn-sm">Kucuk</button>
                    <button class="btn btn-primary btn-lg">Buyuk</button>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------- rozetler --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Rozetler</h2></div>
                <div class="card-ui-body d-flex flex-wrap gap-2">
                    <span class="badge-ui badge-nav">DN Kreatif</span>
                    <span class="badge-ui badge-gold"><i class="bi bi-coin"></i> 120 UC</span>
                    <span class="badge-ui badge-ok"><i class="bi bi-check-circle"></i> Aktif</span>
                    <span class="badge-ui badge-warn"><i class="bi bi-hourglass-split"></i> Beklemede</span>
                    <span class="badge-ui badge-err"><i class="bi bi-slash-circle"></i> Donduruldu</span>
                    <span class="badge-ui badge-nav"><i class="bi bi-star-fill"></i> Ayin Is Insani</span>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------- uyarilar --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Uyari kutulari</h2></div>
                <div class="card-ui-body d-flex flex-column gap-2">
                    <div class="alert-ui alert-ok"><i class="bi bi-check-circle"></i><div>Basvuru onaylandi, hos geldin e-postasi gonderildi.</div></div>
                    <div class="alert-ui alert-warn"><i class="bi bi-exclamation-triangle"></i><div>Kontenjan dolmak uzere: 38 / 40.</div></div>
                    <div class="alert-ui alert-err"><i class="bi bi-x-circle"></i><div>Bu davet baginin suresi dolmus.</div></div>
                    <div class="alert-ui alert-info"><i class="bi bi-info-circle"></i><div>Sunum siraniz toplanti gunu belli olacak.</div></div>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------- form --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Form alanlari</h2></div>
                <div class="card-ui-body">
                    <div class="mb-3">
                        <label class="form-label">Firma adi</label>
                        <input class="form-control" value="Ornek Reklam Ajansi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sektor</label>
                        <select class="form-select">
                            <option>Reklam ve Tanitim</option>
                            <option>Turizm</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">E-posta</label>
                        <input class="form-control is-invalid" value="hatali-adres">
                        <div class="invalid-feedback d-block">Gecerli bir e-posta adresi girin.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------- uye karti --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Uye dizini karti</h2></div>
                <div class="card-ui-body">
                    <div class="card-ui card-hover">
                        <div class="card-ui-body">
                            <div class="d-flex gap-3">
                                <div style="width:52px;height:52px;border-radius:50%;background:var(--nav-100);
                                            display:flex;align-items:center;justify-content:center;
                                            color:var(--nav);font-weight:700;flex:0 0 auto">OU</div>
                                <div class="min-w-0">
                                    <div style="font-weight:600">Ornek Uye</div>
                                    <div class="text-muted-2" style="font-size:.875rem">Kurucu Ortak &middot; Ornek Reklam Ajansi</div>
                                    <div class="mt-2 d-flex gap-1 flex-wrap">
                                        <span class="badge-ui badge-nav">DN Kreatif</span>
                                        <span class="badge-ui badge-nav">Mugla</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted-2 mt-3 mb-3" style="font-size:.875rem">
                                Kurumsal kimlik, sosyal medya yonetimi ve reklam kampanyalari.
                            </p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline btn-sm"><i class="bi bi-whatsapp"></i> WhatsApp</button>
                                <button class="btn btn-outline btn-sm"><i class="bi bi-envelope"></i> E-posta</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------- tablo --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head">
                    <h2>Tablo</h2>
                    <button class="btn btn-outline btn-sm">Excel'e aktar</button>
                </div>
                <div class="card-ui-body p-0">
                    <table class="table-ui">
                        <thead>
                            <tr><th>Uye</th><th>Katilim</th><th>Sunum</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Ornek Uye</td><td><span class="badge-ui badge-ok">Katiliyor</span></td><td>1</td></tr>
                            <tr><td>Ikinci Uye</td><td><span class="badge-ui badge-warn">Yanitsiz</span></td><td>&mdash;</td></tr>
                            <tr><td>Ucuncu Uye</td><td><span class="badge-ui badge-err">Katilmiyor</span></td><td>&mdash;</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------- bos durum --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Bos durum</h2></div>
                <div class="card-ui-body">
                    <div class="empty">
                        <i class="bi bi-calendar-x"></i>
                        Yaklasan etkinlik yok.
                        <div class="mt-2"><button class="btn btn-primary btn-sm">Etkinlik olustur</button></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
