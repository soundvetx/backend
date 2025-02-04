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
    private $token;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $token
    ) {
        $this->user = $user;
        $this->token = $token;
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

        $mail = str_replace('{{ logoUrl }}', Storage::url('logos/logo-full-primary.png'), $mail);
        $mail = str_replace('{{ userName }}', $this->user->name, $mail);
        $mail = str_replace('{{ token }}', $this->token, $mail);
        $mail = str_replace('{{ resetPasswordUrl }}', env('APP_URL') . "/reset-password?token=$this->token", $mail);

        return new Content(
            view: null,
            html: null,
            text: null,
            markdown: null,
            with: [],
            htmlString: $mail,
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
