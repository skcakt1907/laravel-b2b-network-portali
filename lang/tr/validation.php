<?php

/**
 * Turkce dogrulama mesajlari. Yeni kural kullanildikca buraya eklenir;
 * karsiligi olmayan kural Laravel'in Ingilizce metnine duser.
 */
return [
    'required' => ':attribute alani zorunludur.',
    'required_if' => ':other :value oldugunda :attribute alani zorunludur.',
    'email' => ':attribute gecerli bir e-posta adresi olmalidir.',
    'confirmed' => ':attribute tekrari eslesmiyor.',
    'different' => ':attribute ile :other ayni olamaz.',
    'current_password' => 'Mevcut sifre hatali.',
    'unique' => 'Bu :attribute zaten kayitli.',
    'exists' => 'Secilen :attribute gecersiz.',
    'date' => ':attribute gecerli bir tarih olmalidir.',
    'date_format' => ':attribute :format bicimine uymuyor.',
    'numeric' => ':attribute sayi olmalidir.',
    'integer' => ':attribute tam sayi olmalidir.',
    'boolean' => ':attribute dogru veya yanlis olmalidir.',
    'url' => ':attribute gecerli bir adres olmalidir.',
    'image' => ':attribute bir gorsel olmalidir.',
    'mimes' => ':attribute su turlerden biri olmalidir: :values.',
    'accepted' => ':attribute kabul edilmelidir.',
    'in' => 'Secilen :attribute gecersiz.',

    'min' => [
        'string' => ':attribute en az :min karakter olmalidir.',
        'numeric' => ':attribute en az :min olmalidir.',
        'file' => ':attribute en az :min kilobayt olmalidir.',
        'array' => ':attribute en az :min oge icermelidir.',
    ],

    'max' => [
        'string' => ':attribute en fazla :max karakter olabilir.',
        'numeric' => ':attribute en fazla :max olabilir.',
        'file' => ':attribute en fazla :max kilobayt olabilir.',
        'array' => ':attribute en fazla :max oge icerebilir.',
    ],

    'password' => [
        'letters' => ':attribute en az bir harf icermelidir.',
        'mixed' => ':attribute en az bir buyuk ve bir kucuk harf icermelidir.',
        'numbers' => ':attribute en az bir rakam icermelidir.',
        'symbols' => ':attribute en az bir sembol icermelidir.',
        'uncompromised' => 'Bu :attribute bilinen veri sizintilarinda gorundu. Lutfen baska bir sifre secin.',
    ],

    'attributes' => [
        'name' => 'ad soyad',
        'email' => 'e-posta',
        'password' => 'sifre',
        'current_password' => 'mevcut sifre',
        'phone' => 'telefon',
        'title' => 'unvan',
        'company_name' => 'firma adi',
        'sector' => 'sektor',
        'city' => 'sehir',
        'brand' => 'marka',
        'message' => 'mesaj',
    ],
];
