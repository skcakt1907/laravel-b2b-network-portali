<?php

namespace Database\Seeders;

use App\Models\CoinTransaction;
use App\Models\Company;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ---------------------------------------------------------- admin
        $admin = new User();
        $admin->name = 'DN Unity Yonetici';
        $admin->email = 'admin@dnunity.com';
        $admin->password = Hash::make('admin123');
        // Korumali alanlar acikca atanir, mass-assign EDILMEZ
        $admin->role = User::ROL_ADMIN;
        $admin->status = 'aktif';
        $admin->approved_at = now();
        $admin->save();

        // ---------------------------------------------------------- ornek firma + uye
        $firma = Company::create([
            'name' => 'Ornek Reklam Ajansi',
            'slug' => 'ornek-reklam-ajansi',
            'sector' => 'Reklam ve Tanitim',
            'city' => 'Mugla',
            'founded_on' => '2015-04-12',
            'about' => 'Ornek kayit - gercek icerik musteriden gelecek.',
        ]);

        $uye = new User();
        $uye->name = 'Ornek Uye';
        $uye->email = 'uye@dnunity.com';
        $uye->password = Hash::make('uye12345');
        $uye->title = 'Kurucu Ortak';
        $uye->phone = '0500 000 00 00';
        $uye->company_id = $firma->id;
        $uye->role = User::ROL_UYE;
        $uye->status = 'aktif';
        $uye->approved_at = now();
        $uye->approved_by = $admin->id;
        $uye->save();

        Profile::create([
            'user_id' => $uye->id,
            'brand' => 'dnkreatif',
            'services_pitch' => 'Kurumsal kimlik, sosyal medya yonetimi ve reklam kampanyalari.',
            'seeking_pitch' => 'Turizm ve saglik sektorunden uzun soluklu is birlikleri ariyoruz.',
            'birthday' => '1985-07-21',
        ]);

        // ---------------------------------------------------------- ornek etkinlik
        $etkinlik = Event::create([
            'title' => 'Aylik Networking Toplantisi',
            'slug' => 'aylik-networking-toplantisi-'.now()->format('Y-m'),
            'description' => 'Ornek kayit - aylik toplanti gundemi buraya yazilir.',
            'type' => 'online',
            'category' => 'toplanti',
            'starts_at' => now()->addWeek()->setTime(20, 0),
            'ends_at' => now()->addWeek()->setTime(21, 30),
            'online_url' => 'https://zoom.us/j/000000000',
            'capacity' => 40,
            'visibility' => 'uye_misafir',
            'is_published' => true,
            'has_presentations' => true,
        ]);
        $etkinlik->created_by = $admin->id;
        $etkinlik->save();

        $katilim = EventAttendee::create([
            'event_id' => $etkinlik->id,
            'user_id' => $uye->id,
            'rsvp' => 'katiliyor',
            'responded_at' => now(),
        ]);
        $katilim->presentation_order = 1;
        $katilim->save();

        // ---------------------------------------------------------- ornek coin hareketi
        $islem = new CoinTransaction();
        $islem->user_id = $uye->id;
        $islem->amount = 10;
        $islem->type = 'kazanim';
        $islem->reason = 'etkinlik_katilimi';
        $islem->description = 'Ornek kayit - coin kurallari henuz netlesmedi.';
        $islem->source_type = Event::class;
        $islem->source_id = $etkinlik->id;
        $islem->created_by = $admin->id;
        $islem->save();
    }
}
