<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /** Takvimi girisi olan herkes gorebilir; icerik ayrica suzulur. */
    public function viewAny(User $kullanici): bool
    {
        return true;
    }

    /**
     * Yayinlanmamis etkinligi yalnizca admin gorur.
     * Misafir yalnizca kendisine acilmis (uye_misafir) kartlari gorur.
     */
    public function view(User $kullanici, Event $etkinlik): bool
    {
        if ($kullanici->isAdmin()) {
            return true;
        }

        if (! $etkinlik->is_published) {
            return false;
        }

        if ($kullanici->isMisafir()) {
            return $etkinlik->visibility === 'uye_misafir';
        }

        return true;
    }

    /** Katilim onayi verebilmek icin etkinligi gorebilmek gerekir. */
    public function rsvp(User $kullanici, Event $etkinlik): bool
    {
        if (! $this->view($kullanici, $etkinlik)) {
            return false;
        }

        // Gecmis etkinlige katilim onayi verilemez
        return $etkinlik->starts_at->isFuture();
    }

    public function create(User $kullanici): bool
    {
        return $kullanici->isAdmin();
    }

    public function update(User $kullanici, Event $etkinlik): bool
    {
        return $kullanici->isAdmin();
    }

    public function delete(User $kullanici, Event $etkinlik): bool
    {
        return $kullanici->isAdmin();
    }
}
