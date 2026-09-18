@extends('layouts.app')

@section('title', 'Uyelik basvurusu')
@section('subtitle', 'Davet ile basvuru')

@push('styles')
    <style>
        /* Basvuru formu giris kartindan genis olmali */
        .auth-card { max-width: 620px; }
    </style>
@endpush

@section('content')

    <div class="alert-ui alert-info mb-3">
        <i class="bi bi-envelope-paper"></i>
        <div>
            @if($davet->inviter)
                <strong>{{ $davet->inviter->name }}</strong> sizi DN Unity'ye davet etti.
            @else
                DN Unity'ye davet edildiniz.
            @endif
            Basvurunuz yonetim tarafindan incelendikten sonra size e-posta ile donus yapilacak.
        </div>
    </div>

    <form method="POST" action="{{ route('davet.gonder', $davet->code) }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Ad Soyad <span class="text-danger">*</span></label>
                <input class="form-control @error('name') is-invalid @enderror" type="text"
                       name="name" id="name" required
                       value="{{ old('name', $davet->name) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="email">E-posta <span class="text-danger">*</span></label>
                <input class="form-control @error('email') is-invalid @enderror" type="email"
                       name="email" id="email" required
                       value="{{ old('email', $davet->email) }}">
                <div class="form-hint">Onaylanirsa giris bilgileriniz bu adrese gonderilir.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="phone">Telefon</label>
                <input class="form-control" type="text" name="phone" id="phone"
                       value="{{ old('phone') }}" placeholder="0500 000 00 00">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="company_name">Firma adi <span class="text-danger">*</span></label>
                <input class="form-control @error('company_name') is-invalid @enderror" type="text"
                       name="company_name" id="company_name" required
                       value="{{ old('company_name') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="sector">Sektor</label>
                <input class="form-control" type="text" name="sector" id="sector"
                       value="{{ old('sector') }}" placeholder="Reklam, turizm, insaat...">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="city">Sehir</label>
                <input class="form-control" type="text" name="city" id="city"
                       value="{{ old('city') }}">
            </div>

            <div class="col-12">
                <label class="form-label" for="brand">Hangi marka ile calisiyorsunuz?</label>
                <select class="form-select" name="brand" id="brand">
                    <option value="">Seciniz</option>
                    @foreach($markalar as $deger => $etiket)
                        <option value="{{ $deger }}" @selected(old('brand') === $deger)>{{ $etiket }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label class="form-label" for="reference_name">Referansiniz</label>
                <input class="form-control" type="text" name="reference_name" id="reference_name"
                       value="{{ old('reference_name') }}"
                       placeholder="Sizi yonlendiren kisi">
            </div>

            <div class="col-12">
                <label class="form-label" for="message">Kisaca kendinizden ve isinizden bahsedin</label>
                <textarea class="form-control" name="message" id="message" rows="4"
                          maxlength="1000">{{ old('message') }}</textarea>
                <div class="form-hint">En fazla 1000 karakter.</div>
            </div>
        </div>

        <button class="btn btn-primary w-100 mt-4" type="submit">Basvuruyu gonder</button>
    </form>

@endsection
