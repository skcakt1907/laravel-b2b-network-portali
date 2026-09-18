@extends('layouts.panel')

@section('title', $uye->name)
@section('subtitle', $uye->email)

@section('content')

    <div class="mb-3">
        <a class="btn btn-ghost btn-sm" href="{{ route('yonetim.uyeler.index') }}">
            <i class="bi bi-arrow-left"></i> Uye listesi
        </a>
    </div>

    @php $kendisi = auth()->id() === $uye->id; @endphp

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-ui">
                <div class="card-ui-head">
                    <h2>Uye bilgileri</h2>
                    @include('yonetim.uyeler.durum-rozeti', ['durum' => $uye->status])
                </div>
                <div class="card-ui-body p-0">
                    <table class="table-ui">
                        <tbody>
                            @foreach ([
                                'Ad Soyad' => $uye->name,
                                'E-posta' => $uye->email,
                                'Telefon' => $uye->phone,
                                'Unvan' => $uye->title,
                                'Firma' => $uye->company?->name,
                                'Rol' => ['admin' => 'Yonetici', 'uye' => 'Uye', 'misafir' => 'Misafir'][$uye->role],
                                'Marka' => $uye->profile?->brandLabel(),
                                'Onaylayan' => $uye->approvedBy?->name,
                                'Onay tarihi' => $uye->approved_at?->format('d.m.Y H:i'),
                                'Son giris' => $uye->last_login_at?->format('d.m.Y H:i'),
                                'Unitycoin' => number_format($coinBakiye, 0, ',', '.').' UC',
                                'Katildigi etkinlik' => $katilimSayisi,
                            ] as $etiket => $deger)
                                <tr>
                                    <th style="width:38%">{{ $etiket }}</th>
                                    <td>{{ ($deger === null || $deger === '') ? '—' : $deger }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">

            @if($kendisi)
                <div class="alert-ui alert-info">
                    <i class="bi bi-person-check"></i>
                    <div>
                        Bu sizin hesabiniz. Kendi uyeliginizi donduramaz veya silemezsiniz;
                        bu kural yoneticinin yanlislikla sistemden kilitlenmesini onler.
                    </div>
                </div>
            @else

                <div class="card-ui mb-3">
                    <div class="card-ui-head"><h2>Uyelik durumu</h2></div>
                    <div class="card-ui-body d-flex flex-column gap-2">

                        @if($uye->status !== 'aktif')
                            <form method="POST" action="{{ route('yonetim.uyeler.durum', $uye) }}">
                                @csrf
                                <input type="hidden" name="islem" value="aktiflestir">
                                <button class="btn btn-primary w-100" type="submit">
                                    <i class="bi bi-check-circle"></i> Aktiflestir
                                </button>
                            </form>
                        @endif

                        @if($uye->status !== 'donduruldu')
                            <form method="POST" action="{{ route('yonetim.uyeler.durum', $uye) }}">
                                @csrf
                                <input type="hidden" name="islem" value="dondur">
                                <button class="btn btn-outline w-100" type="submit">
                                    <i class="bi bi-pause-circle"></i> Dondur (gecici)
                                </button>
                            </form>
                        @endif

                        @if($uye->status !== 'pasif')
                            <form method="POST" action="{{ route('yonetim.uyeler.durum', $uye) }}">
                                @csrf
                                <input type="hidden" name="islem" value="pasifeCek">
                                <button class="btn btn-outline w-100" type="submit">
                                    <i class="bi bi-slash-circle"></i> Pasife cek (kalici)
                                </button>
                            </form>
                        @endif

                        <div class="form-hint">
                            Dondurma geri alinabilir ve veriler korunur. Dondurulan uyenin
                            acik oturumu bir sonraki istekte kapanir.
                        </div>
                    </div>
                </div>

                <div class="card-ui" style="border-color:rgba(192,57,43,.35)">
                    <div class="card-ui-head">
                        <h2 style="color:var(--err)">KVKK - veri silme</h2>
                    </div>
                    <div class="card-ui-body">
                        <p class="text-muted-2" style="font-size:.875rem">
                            Uyenin kisisel verileri (ad, e-posta, telefon, fotograf, profil
                            metinleri) geri donusu olmayacak sekilde silinir. Kayit tamamen
                            kaldirilmaz; Unitycoin hareketleri ve etkinlik katilim gecmisi
                            anonim olarak korunur, aksi halde toplulugun gecmis verisi bozulur.
                        </p>

                        <form method="POST" action="{{ route('yonetim.uyeler.anonimlestir', $uye) }}">
                            @csrf
                            <label class="form-label" for="onay_adi">
                                Onaylamak icin uyenin adini birebir yazin
                            </label>
                            <input class="form-control mb-2" type="text" name="onay_adi" id="onay_adi"
                                   placeholder="{{ $uye->name }}" autocomplete="off" required>
                            <button class="btn btn-outline w-100" type="submit"
                                    style="border-color:var(--err);color:var(--err)">
                                <i class="bi bi-trash"></i> Kisisel verileri sil
                            </button>
                        </form>
                    </div>
                </div>

            @endif
        </div>
    </div>

@endsection
