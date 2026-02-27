<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificatSigne extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomEtudiant,
        public string $niveauValide,
        public string $filiere,
        public string $nomResponsable,
        public string $codeVerification,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '🎓 Votre certificat a été signé officiellement');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.certificat_signe');
    }
}