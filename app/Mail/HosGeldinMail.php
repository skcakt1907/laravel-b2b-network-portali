<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HosGeldinMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $uye,
        /** Yalnizca bu e-postada goruntulenir; hicbir yerde saklanmaz. */
        public string $geciciSifre,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'DN Unity uyeliginiz onaylandi');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.hos-geldin');
    }
}
