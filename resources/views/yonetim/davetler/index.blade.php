@extends('layouts.panel')

@section('title', 'Davet baglari')
@section('subtitle', 'DN Unity kapali devredir; uyelik yalnizca davet bagi ile basvurulabilir.')

@section('content')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-ui">
                <div class="card-ui-head"><h2>Yeni davet</h2></div>
                <div class="card-ui-body">
                    <form method="POST" action="{{ route('yonetim.davetler.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="name">Aday adi</label>
                            <input class="form-control" type="text" name="name" id="name"
                                   value="{{ old('name') }}">
                            <div class="form-hint">Forma on dolgu olarak gelir.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Aday e-postasi</label>
                            <input class="form-control @error('email') is-invalid @enderror"
                                   type="email" name="email" id="email" value="{{ old('email') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="gun">Gecerlilik (gun)</label>
                            <input class="form-control @error('gun') is-invalid @enderror"
                                   type="number" name="gun" id="gun" min="1" max="365"
                                   value="{{ old('gun', 14) }}" required>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">Davet bagi olustur</button>
                    </form>

                    <div class="alert-ui alert-warn mt-3">
                        <i class="bi bi-send"></i>
                        <div style="font-size:.875rem">
                            Bag otomatik gonderilmez. Olusturduktan sonra kopyalayip
                            adaya kendiniz iletmelisiniz.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-ui">
                <div class="card-ui-head"><h2>Olusturulan davetler</h2></div>
                <div class="card-ui-body p-0">
                    @if($davetler->isEmpty())
                        <div class="empty">
                            <i class="bi bi-envelope"></i>
                            Henuz davet olusturulmadi.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table-ui">
                                <thead>
                                    <tr>
                                        <th>Aday</th>
                                        <th>Durum</th>
                                        <th>Bag</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($davetler as $davet)
                                        @php $bag = route('davet.form', $davet->code); @endphp
                                        <tr>
                                            <td>
                                                <div style="font-weight:600">{{ $davet->name ?: 'Isimsiz' }}</div>
                                                <div class="text-muted-2" style="font-size:.8125rem">
                                                    {{ $davet->email ?: '—' }}
                                                </div>
                                            </td>
                                            <td>
                                                @if($davet->used_at)
                                                    <span class="badge-ui badge-ok"><i class="bi bi-check-circle"></i> Kullanildi</span>
                                                @elseif(! $davet->isKullanilabilir())
                                                    <span class="badge-ui badge-err"><i class="bi bi-clock-history"></i> Suresi doldu</span>
                                                @else
                                                    <span class="badge-ui badge-warn">
                                                        <i class="bi bi-hourglass-split"></i>
                                                        {{ $davet->expires_at?->format('d.m.Y') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td style="max-width:320px">
                                                @if($davet->isKullanilabilir())
                                                    <input class="form-control form-control-sm" readonly
                                                           value="{{ $bag }}"
                                                           onclick="this.select();document.execCommand('copy')"
                                                           title="Tiklayinca kopyalanir"
                                                           style="font-size:.75rem;font-family:monospace">
                                                @else
                                                    <span class="text-muted-2" style="font-size:.8125rem">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if(! $davet->used_at)
                                                    <form method="POST"
                                                          action="{{ route('yonetim.davetler.destroy', $davet) }}"
                                                          onsubmit="return confirm('Bu davet bagi iptal edilecek. Emin misiniz?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-ghost btn-sm" type="submit" title="Iptal et">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            @if($davetler->hasPages())
                <div class="mt-3">{{ $davetler->links() }}</div>
            @endif
        </div>
    </div>

@endsection
