<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NoteAttribuee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomEtudiant,
        public string $titreCours,
        public float  $note,
        public string $mention,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '📊 Nouvelle note attribuée — ' . $this->titreCours);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.note_attribuee');
    }
}