<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnonceGenerale extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomDestinataire,
        public string $sujet,
        public string $contenu,
        public string $expediteur,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '📢 ' . $this->sujet);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.annonce_generale');
    }
}