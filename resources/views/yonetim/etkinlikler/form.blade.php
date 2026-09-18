@extends('layouts.panel')

@php $yeni = ! $etkinlik->exists; @endphp

@section('title', $yeni ? 'Yeni etkinlik' : $etkinlik->title)

@section('content')

    <div class="mb-3">
        <a class="btn btn-ghost btn-sm" href="{{ route('yonetim.etkinlikler.index') }}">
            <i class="bi bi-arrow-left"></i> Etkinlik listesi
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <form method="POST"
                  action="{{ $yeni ? route('yonetim.etkinlikler.kaydet') : route('yonetim.etkinlikler.guncelle', $etkinlik) }}">
                @csrf

                <div class="card-ui">
                    <div class="card-ui-head"><h2>Etkinlik bilgileri</h2></div>
                    <div class="card-ui-body">

                        <div class="mb-3">
                            <label class="form-label" for="title">Baslik <span class="text-danger">*</span></label>
                            <input class="form-control @error('title') is-invalid @enderror" type="text"
                                   name="title" id="title" required
                                   value="{{ old('title', $etkinlik->title) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">Aciklama / gundem</label>
                            <textarea class="form-control" name="description" id="description"
                                      rows="4" maxlength="5000">{{ old('description', $etkinlik->description) }}</textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="category">Tur <span class="text-danger">*</span></label>
                                <select class="form-select" name="category" id="category">
                                    @foreach(['toplanti' => 'Toplanti', 'egitim' => 'Egitim', 'gezi' => 'Gezi', 'diger' => 'Diger'] as $d => $e)
                                        <option value="{{ $d }}" @selected(old('category', $etkinlik->category ?? 'toplanti') === $d)>{{ $e }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label" for="type">Bicim <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="type">
                                    <option value="online" @selected(old('type', $etkinlik->type ?? 'online') === 'online')>Online</option>
                                    <option value="fiziksel" @selected(old('type', $etkinlik->type) === 'fiziksel')>Fiziksel</option>
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label" for="starts_at">Baslangic <span class="text-danger">*</span></label>
                                <input class="form-control @error('starts_at') is-invalid @enderror"
                                       type="datetime-local" name="starts_at" id="starts_at" required
                                       value="{{ old('starts_at', $etkinlik->starts_at?->format('Y-m-d\TH:i')) }}">
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label" for="ends_at">Bitis</label>
                                <input class="form-control @error('ends_at') is-invalid @enderror"
                                       type="datetime-local" name="ends_at" id="ends_at"
                                       value="{{ old('ends_at', $etkinlik->ends_at?->format('Y-m-d\TH:i')) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="online_url">Toplanti bagi (Zoom / Teams)</label>
                                <input class="form-control @error('online_url') is-invalid @enderror" type="url"
                                       name="online_url" id="online_url" placeholder="https://zoom.us/j/..."
                                       value="{{ old('online_url', $etkinlik->online_url) }}">
                                <div class="form-hint">Yalnizca katilacagini bildiren uyelere gosterilir.</div>
                            </div>

                            <div class="col-sm-8">
                                <label class="form-label" for="location">Konum (fiziksel etkinlik)</label>
                                <input class="form-control" type="text" name="location" id="location"
                                       value="{{ old('location', $etkinlik->location) }}">
                            </div>

                            <div class="col-sm-4">
                                <label class="form-label" for="capacity">Kontenjan</label>
                                <input class="form-control @error('capacity') is-invalid @enderror" type="number"
                                       name="capacity" id="capacity" min="1" max="5000"
                                       value="{{ old('capacity', $etkinlik->capacity) }}">
                                <div class="form-hint">Bos = sinirsiz</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="visibility">Kimler gorebilir?</label>
                                <select class="form-select" name="visibility" id="visibility">
                                    <option value="uye" @selected(old('visibility', $etkinlik->visibility ?? 'uye') === 'uye')>
                                        Yalnizca uyeler
                                    </option>
                                    <option value="uye_misafir" @selected(old('visibility', $etkinlik->visibility) === 'uye_misafir')>
                                        Uyeler ve misafirler
                                    </option>
                                </select>
                            </div>
                        </div>

                        <hr style="border-color:var(--line)">

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="has_presentations"
                                   id="has_presentations" value="1"
                                   @checked(old('has_presentations', $etkinlik->has_presentations))>
                            <label class="form-check-label" for="has_presentations">
                                3 dakikalik sunum sirasi uygulanacak
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published"
                                   id="is_published" value="1"
                                   @checked(old('is_published', $etkinlik->is_published))>
                            <label class="form-check-label" for="is_published">
                                <strong>Yayinda</strong> — uyeler gorebilsin
                            </label>
                        </div>

                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check-lg"></i> {{ $yeni ? 'Olustur' : 'Kaydet' }}
                    </button>
                </div>
            </form>

            @if(! $yeni)
                <form method="POST" action="{{ route('yonetim.etkinlikler.sil', $etkinlik) }}" class="mt-2"
                      onsubmit="return confirm('Bu etkinlik silinecek. Emin misiniz?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-ghost btn-sm" type="submit" style="color:var(--err)">
                        <i class="bi bi-trash"></i> Etkinligi sil
                    </button>
                </form>
            @endif
        </div>

        {{-- ------------------------------------------------ katilimcilar --}}
        @if(! $yeni)
            <div class="col-lg-5">
                <div class="card-ui">
                    <div class="card-ui-head">
                        <h2>Katilimcilar</h2>
                        <a class="btn btn-outline btn-sm"
                           href="{{ route('yonetim.etkinlikler.disaAktar', $etkinlik) }}">
                            <i class="bi bi-download"></i> Excel
                        </a>
                    </div>
                    <div class="card-ui-body">

                        @php $katilanlar = $katilimcilar->where('rsvp', 'katiliyor'); @endphp

                        @if($katilimcilar->isEmpty())
                            <div class="empty">
                                <i class="bi bi-people"></i>
                                Henuz katilim yaniti yok.
                            </div>
                        @else
                            <p class="text-muted-2" style="font-size:.875rem">
                                {{ $katilanlar->count() }} katiliyor,
                                {{ $katilimcilar->where('rsvp', 'katilmiyor')->count() }} katilmiyor.
                            </p>

                            @if($etkinlik->has_presentations)
                                <form method="POST" action="{{ route('yonetim.etkinlikler.sirala', $etkinlik) }}"
                                      onsubmit="return confirm('Sunum sirasi bastan uretilecek, mevcut sira silinecek. Devam edilsin mi?')">
                                    @csrf
                                    <button class="btn btn-gold w-100 mb-3" type="submit">
                                        <i class="bi bi-shuffle"></i> Sunum sirasini uret
                                    </button>
                                </form>
                                <div class="form-hint mb-3">
                                    Sira, katilacagini bildirenler arasinda rastgele dagitilir;
                                    boylece her ay ayni kisiler basa dusmez.
                                </div>
                            @endif

                            {{-- Tablo herhangi bir formun ICINDE degil; alanlar asagidaki
                                 iki forma form="" ozniteligiyle baglanir. Boylece ic ice
                                 form olusmaz ve sira/yoklama ayri ayri kaydedilebilir. --}}
                            <table class="table-ui">
                                    <thead>
                                        <tr>
                                            @if($etkinlik->has_presentations)<th style="width:72px">Sira</th>@endif
                                            <th>Uye</th>
                                            <th style="width:60px">Geldi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($katilimcilar as $k)
                                            <tr @class(['text-muted-2' => $k->rsvp !== 'katiliyor'])>
                                                @if($etkinlik->has_presentations)
                                                    <td>
                                                        @if($k->rsvp === 'katiliyor')
                                                            <input class="form-control form-control-sm" type="number"
                                                                   name="sira[{{ $k->id }}]" min="1" max="999"
                                                                   value="{{ $k->presentation_order }}"
                                                                   form="sira-formu" style="width:64px">
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                @endif
                                                <td>
                                                    {{ $k->user->name }}
                                                    @if($k->rsvp === 'katilmiyor')
                                                        <span class="badge-ui badge-err">Yok</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input class="form-check-input" type="checkbox"
                                                           name="katildi[]" value="{{ $k->id }}"
                                                           form="yoklama-formu" @checked($k->attended)>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            <div class="d-flex gap-2 mt-3">
                                @if($etkinlik->has_presentations)
                                    <button class="btn btn-outline btn-sm flex-grow-1" type="submit" form="sira-formu">
                                        Sirayi kaydet
                                    </button>
                                @endif
                                <button class="btn btn-outline btn-sm flex-grow-1" type="submit" form="yoklama-formu">
                                    Yoklamayi kaydet
                                </button>
                            </div>

                            {{-- Ic ice form olmasin diye alanlar form="" ile disaridan baglanir --}}
                            <form method="POST" id="sira-formu"
                                  action="{{ route('yonetim.etkinlikler.siraKaydet', $etkinlik) }}">@csrf</form>
                            <form method="POST" id="yoklama-formu"
                                  action="{{ route('yonetim.etkinlikler.yoklama', $etkinlik) }}">@csrf</form>
                        @endif

                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection
