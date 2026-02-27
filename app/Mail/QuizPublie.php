<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizPublie extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomEtudiant,
        public string $titreQuiz,
        public string $filiere,
        public int    $dureeMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '📝 Nouveau quiz disponible — ' . $this->titreQuiz);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quiz_publie');
    }
}