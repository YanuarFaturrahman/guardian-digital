<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email peringatan sistem untuk superadmin (teks polos, tanpa data pribadi pengguna). */
class SystemAlertMail extends Mailable
{
    public function __construct(public string $alertSubject, public string $alertBody)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Guardian Digital] ' . $this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.system-alert', with: ['body' => $this->alertBody, 'url' => config('app.url')]);
    }
}
