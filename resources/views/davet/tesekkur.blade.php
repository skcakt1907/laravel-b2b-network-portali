@extends('layouts.app')

@section('title', 'Basvurunuz alindi')
@section('subtitle', 'Tesekkurler')

@section('content')

    <div class="empty">
        <i class="bi bi-check-circle" style="color:var(--ok);opacity:1"></i>
        <strong style="color:var(--ink);font-size:1.05rem">Basvurunuz alindi</strong>
        <p class="mt-2 mb-0">
            DN Unity yonetimi basvurunuzu inceleyecek. Sonuc ne olursa olsun
            belirttiginiz e-posta adresine donus yapilacak.
        </p>
        <p class="mt-2 mb-0" style="font-size:.875rem">
            Onaylanirsa giris bilgileriniz ayni e-postaya gonderilir.
        </p>
    </div>

@endsection
