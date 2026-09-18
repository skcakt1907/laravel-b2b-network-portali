@extends('layouts.panel')

@section('title', $basvuru->name)
@section('subtitle', 'Uyelik basvurusu · '.$basvuru->created_at->format('d.m.Y H:i'))

@section('content')

    <div class="mb-3">
        <a class="btn btn-ghost btn-sm" href="{{ route('yonetim.basvurular.index') }}">
            <i class="bi bi-arrow-left"></i> Basvuru listesi
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-ui">
                <div class="card-ui-head">
                    <h2>Basvuru bilgileri</h2>
                    @include('yonetim.basvurular.durum-rozeti', ['durum' => $basvuru->status])
                </div>
                <div class="card-ui-body p-0">
                    <table class="table-ui">
                        <tbody>
                            @foreach ([
                                'Ad Soyad' => $basvuru->name,
                                'E-posta' => $basvuru->email,
                                'Telefon' => $basvuru->phone,
                                'Firma' => $basvuru->company_name,
                                'Sektor' => $basvuru->sector,
                                'Sehir' => $basvuru->city,
                                'Marka' => $basvuru->brand ? (App\Models\Profile::MARKA_ETIKETLERI[$basvuru->brand] ?? $basvuru->brand) : null,
                                'Referans' => $basvuru->reference_name,
                                'Davet eden' => $basvuru->invitation?->inviter?->name,
                            ] as $etiket => $deger)
                                <tr>
                                    <th style="width:38%">{{ $etiket }}</th>
                                    <td>{{ $deger ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($basvuru->message)
                        <div style="padding:1rem 1.25rem;border-top:1px solid var(--line)">
                            <div class="text-muted-2 mb-1" style="font-size:.8125rem">Adayin mesaji</div>
                            <div style="white-space:pre-line">{{ $basvuru->message }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @if($basvuru->isBeklemede())
                <div class="card-ui mb-3">
                    <div class="card-ui-head"><h2>Karar</h2></div>
                    <div class="card-ui-body">

                        <p class="text-muted-2" style="font-size:.875rem">
                            Onaylarsaniz uye hesabi olusturulur, gecici sifre uretilir ve
                            adaya e-posta ile gonderilir. Firma adi mevcut bir firmayla
                            eslesirse ona baglanir, eslesmezse yeni firma acilir.
                        </p>

                        <form method="POST" action="{{ route('yonetim.basvurular.onayla', $basvuru) }}"
                              onsubmit="return confirm('{{ $basvuru->name }} uye olarak eklenecek ve gecici sifre e-postayla gonderilecek. Onayliyor musunuz?')">
                            @csrf
                            <button class="btn btn-primary w-100 mb-3" type="submit">
                                <i class="bi bi-check-circle"></i> Onayla ve uye olustur
                            </button>
                        </form>

                        <hr style="border-color:var(--line)">

                        <form method="POST" action="{{ route('yonetim.basvurular.reddet', $basvuru) }}">
                            @csrf
                            <label class="form-label" for="admin_note">Ret notu (ic kayit)</label>
                            <textarea class="form-control mb-2" name="admin_note" id="admin_note" rows="3"
                                      placeholder="Neden reddedildi? Bu not adaya gonderilmez."></textarea>
                            <button class="btn btn-outline w-100" type="submit">
                                <i class="bi bi-x-circle"></i> Reddet
                            </button>
                        </form>

                    </div>
                </div>
            @else
                <div class="card-ui">
                    <div class="card-ui-head"><h2>Sonuc</h2></div>
                    <div class="card-ui-body">
                        <p class="mb-2">
                            <strong>{{ $basvuru->reviewer?->name ?? 'Bilinmiyor' }}</strong>
                            tarafindan {{ $basvuru->reviewed_at?->format('d.m.Y H:i') }} tarihinde
                            {{ $basvuru->status === 'onaylandi' ? 'onaylandi' : 'reddedildi' }}.
                        </p>

                        @if($basvuru->createdUser)
                            <p class="mb-2" style="font-size:.9375rem">
                                Olusturulan uye: <strong>{{ $basvuru->createdUser->name }}</strong>
                                @if($basvuru->createdUser->must_change_password)
                                    <span class="badge-ui badge-warn ms-1">Gecici sifre henuz degistirilmedi</span>
                                @endif
                            </p>
                        @endif

                        @if($basvuru->admin_note)
                            <div class="alert-ui alert-info mt-3">
                                <i class="bi bi-sticky"></i>
                                <div style="white-space:pre-line">{{ $basvuru->admin_note }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection
