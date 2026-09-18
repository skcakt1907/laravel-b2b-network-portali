<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Gecici: tasarim sistemi on izlemesi. Yayina cikmadan once kaldirilacak.
if (config('app.debug')) {
    Route::view('/tasarim', 'tasarim');
}
