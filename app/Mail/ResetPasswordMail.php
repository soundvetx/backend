<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    private $user;
    private $url;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $url
    ) {
        $this->user = $user;
        $this->url = $url;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SoundvetX - Redefinição de Senha',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $mail = File::get(resource_path('templates/ResetPasswordMail.html'));

        $mail = str_replace('{{ logoUrl }}', Storage::url('logo/logo_full_primary.png'), $mail);
        $mail = str_replace('{{ userName }}', $this->user->name, $mail);
        $mail = str_replace('{{ resetPasswordUrl }}', $this->url, $mail);

        return new Content(
            html: $mail
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
