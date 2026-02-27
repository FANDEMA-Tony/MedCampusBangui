<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouveauMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomDestinataire,
        public string $nomExpediteur,
        public string $sujet,
        public string $apercu,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '📬 Nouveau message — ' . $this->sujet);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.nouveau_message');
    }
}