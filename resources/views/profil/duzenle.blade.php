@extends('layouts.panel')

@section('title', 'Profilim')
@section('subtitle', 'Uye dizininde nasil gorundugunuzu buradan yonetirsiniz.')

@section('content')

<form method="POST" action="{{ route('profilim.guncelle') }}" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">

        {{-- ------------------------------------------------ kisisel --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Kisisel bilgiler</h2></div>
                <div class="card-ui-body">

                    <div class="d-flex align-items-center gap-3 mb-3">
                        @include('partials.avatar', ['uye' => $uye, 'boyut' => 72])
                        <div class="flex-grow-1">
                            <label class="form-label" for="avatar">Profil fotografi</label>
                            <input class="form-control form-control-sm @error('avatar') is-invalid @enderror"
                                   type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp">
                            <div class="form-hint">JPG, PNG veya WebP. Kare olacak sekilde kirpilir.</div>
                            @if($uye->avatar_path)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="avatar_sil" id="avatar_sil" value="1">
                                    <label class="form-check-label" for="avatar_sil" style="font-size:.8125rem">
                                        Fotografi kaldir
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="name">Ad Soyad <span class="text-danger">*</span></label>
                        <input class="form-control @error('name') is-invalid @enderror" type="text"
                               name="name" id="name" required value="{{ old('name', $uye->name) }}">
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="title">Unvan</label>
                            <input class="form-control" type="text" name="title" id="title"
                                   value="{{ old('title', $uye->title) }}" placeholder="Kurucu Ortak">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="phone">Telefon</label>
                            <input class="form-control" type="text" name="phone" id="phone"
                                   value="{{ old('phone', $uye->phone) }}">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="brand">Hangi marka ile calisiyorsunuz?</label>
                        <select class="form-select" name="brand" id="brand">
                            <option value="">Belirtmek istemiyorum</option>
                            @foreach($markalar as $deger => $etiket)
                                <option value="{{ $deger }}" @selected(old('brand', $profil->brand) === $deger)>
                                    {{ $etiket }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">Profilinizde rozet olarak gorunur.</div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ------------------------------------------------ asansor cumlesi --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>3 dakikalik asansor cumleniz</h2></div>
                <div class="card-ui-body">

                    <p class="text-muted-2" style="font-size:.875rem">
                        Toplantilarda kendinizi anlatirken kullandiginiz iki cumle.
                        Uye dizininde ve aramalarda bu metinler taranir.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="services_pitch">Hizmetlerimiz / Ne yapiyoruz?</label>
                        <textarea class="form-control" name="services_pitch" id="services_pitch"
                                  rows="4" maxlength="600"
                                  placeholder="Kurumsal kimlik, sosyal medya yonetimi ve reklam kampanyalari."
                                  oninput="document.getElementById('sayac1').textContent = this.value.length"
                        >{{ old('services_pitch', $profil->services_pitch) }}</textarea>
                        <div class="form-hint">
                            <span id="sayac1">{{ mb_strlen((string) old('services_pitch', $profil->services_pitch)) }}</span> / 600
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="seeking_pitch">Arayislarimiz / Nasil bir is birligi ariyoruz?</label>
                        <textarea class="form-control" name="seeking_pitch" id="seeking_pitch"
                                  rows="4" maxlength="600"
                                  placeholder="Turizm ve saglik sektorunden uzun soluklu is birlikleri ariyoruz."
                                  oninput="document.getElementById('sayac2').textContent = this.value.length"
                        >{{ old('seeking_pitch', $profil->seeking_pitch) }}</textarea>
                        <div class="form-hint">
                            <span id="sayac2">{{ mb_strlen((string) old('seeking_pitch', $profil->seeking_pitch)) }}</span> / 600
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ------------------------------------------------ iletisim --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head"><h2>Iletisim ve sosyal medya</h2></div>
                <div class="card-ui-body">

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="whatsapp">WhatsApp numarasi</label>
                            <input class="form-control" type="text" name="whatsapp" id="whatsapp"
                                   value="{{ old('whatsapp', $profil->whatsapp) }}" placeholder="05001112233">
                            <div class="form-hint">Dizinde hizli mesaj butonu olur.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="website">Kisisel web adresi</label>
                            <input class="form-control @error('website') is-invalid @enderror" type="url"
                                   name="website" id="website" value="{{ old('website', $profil->website) }}"
                                   placeholder="https://">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="linkedin">LinkedIn</label>
                            <input class="form-control @error('linkedin') is-invalid @enderror" type="url"
                                   name="linkedin" id="linkedin" value="{{ old('linkedin', $profil->linkedin) }}"
                                   placeholder="https://linkedin.com/in/...">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="instagram">Instagram</label>
                            <input class="form-control" type="text" name="instagram" id="instagram"
                                   value="{{ old('instagram', $profil->instagram) }}" placeholder="kullaniciadi">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="birthday">Dogum gununuz</label>
                            <input class="form-control @error('birthday') is-invalid @enderror" type="date"
                                   name="birthday" id="birthday"
                                   value="{{ old('birthday', $profil->birthday?->format('Y-m-d')) }}">
                            <div class="form-hint">Yalnizca yonetim kutlama icin gorur.</div>
                        </div>
                    </div>

                    <hr style="border-color:var(--line)">

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_listed" id="is_listed" value="1"
                               @checked(old('is_listed', $profil->is_listed))>
                        <label class="form-check-label" for="is_listed">
                            <strong>Uye dizininde gorunmek istiyorum</strong>
                        </label>
                        <div class="form-hint">
                            Kapatirsaniz diger uyeler sizi dizinde bulamaz. Etkinliklere
                            katiliminiz etkilenmez.
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ------------------------------------------------ firma --}}
        <div class="col-lg-6">
            <div class="card-ui h-100">
                <div class="card-ui-head">
                    <h2>Firma bilgileri</h2>
                    @if($firma)<span class="badge-ui badge-nav">{{ $firma->name }}</span>@endif
                </div>
                <div class="card-ui-body">

                    @if($firma === null)
                        <div class="empty">
                            <i class="bi bi-building"></i>
                            Hesabiniza bagli bir firma yok.
                            <div class="text-muted-2 mt-2" style="font-size:.875rem">
                                Firma baglantisi yonetim tarafindan yapilir.
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if($firma->logo_path)
                                <img src="{{ asset($firma->logo_path) }}" alt="{{ $firma->name }}"
                                     style="width:72px;height:72px;object-fit:contain;
                                            border:1px solid var(--line);border-radius:8px;background:#fff">
                            @endif
                            <div class="flex-grow-1">
                                <label class="form-label" for="company_logo">Firma logosu</label>
                                <input class="form-control form-control-sm @error('company_logo') is-invalid @enderror"
                                       type="file" name="company_logo" id="company_logo"
                                       accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="company_sector">Sektor</label>
                                <input class="form-control" type="text" name="company_sector" id="company_sector"
                                       value="{{ old('company_sector', $firma->sector) }}"
                                       list="sektor-onerileri">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="company_city">Sehir</label>
                                <input class="form-control" type="text" name="company_city" id="company_city"
                                       value="{{ old('company_city', $firma->city) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="company_phone">Firma telefonu</label>
                                <input class="form-control" type="text" name="company_phone" id="company_phone"
                                       value="{{ old('company_phone', $firma->phone) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="company_email">Firma e-postasi</label>
                                <input class="form-control @error('company_email') is-invalid @enderror" type="email"
                                       name="company_email" id="company_email"
                                       value="{{ old('company_email', $firma->email) }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="company_website">Web adresi</label>
                                <input class="form-control @error('company_website') is-invalid @enderror" type="url"
                                       name="company_website" id="company_website"
                                       value="{{ old('company_website', $firma->website) }}" placeholder="https://">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="company_founded_on">Kurulus tarihi</label>
                                <input class="form-control" type="date" name="company_founded_on" id="company_founded_on"
                                       value="{{ old('company_founded_on', $firma->founded_on?->format('Y-m-d')) }}">
                                <div class="form-hint">Yildonumu kutlamasi icin.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="company_about">Firma hakkinda</label>
                                <textarea class="form-control" name="company_about" id="company_about"
                                          rows="3" maxlength="1500">{{ old('company_about', $firma->about) }}</textarea>
                            </div>
                        </div>

                        <div class="alert-ui alert-warn mt-3">
                            <i class="bi bi-people"></i>
                            <div style="font-size:.8125rem">
                                Ayni firmadan baska uyeler varsa bu bilgiler onlar icin de degisir.
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check-lg"></i> Degisiklikleri kaydet
        </button>
        {{-- Misafirin uye dizinine erisimi yok; bag da gosterilmez --}}
        @can('viewAny', App\Models\User::class)
            <a class="btn btn-outline" href="{{ route('uyeler.show', $uye) }}">
                Dizindeki gorunumum
            </a>
        @endcan
    </div>

</form>

@endsection
