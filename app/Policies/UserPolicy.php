<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Uye dizini misafirlere kapalidir. */
    public function viewAny(User $kullanici): bool
    {
        return ! $kullanici->isMisafir();
    }

    /**
     * Bir uyenin profilini gorme hakki.
     * Kendi profilini herkes gorur; baskasininkini yalnizca uyeler,
     * o da profil dizinde listelenmeye acikken.
     */
    public function view(User $kullanici, User $hedef): bool
    {
        if ($kullanici->is($hedef) || $kullanici->isAdmin()) {
            return true;
        }

        if ($kullanici->isMisafir()) {
            return false;
        }

        // Onaysiz veya dondurulmus hesaplar dizinde gorunmez
        if ($hedef->status !== 'aktif') {
            return false;
        }

        return $hedef->profile?->is_listed ?? false;
    }

    /** Profil duzenleme: kendi profili ya da admin. */
    public function update(User $kullanici, User $hedef): bool
    {
        return $kullanici->is($hedef) || $kullanici->isAdmin();
    }

    /** Uyelik dondurma/silme yalnizca admin isidir. */
    public function manage(User $kullanici, User $hedef): bool
    {
        // Admin kendi hesabini kapatamaz; yanlislikla disarida kalmayi onler
        return $kullanici->isAdmin() && ! $kullanici->is($hedef);
    }

    /** Misafir davet etme hakki uyelere aittir. */
    public function inviteGuest(User $kullanici): bool
    {
        return ! $kullanici->isMisafir();
    }
}
