<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ExamRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    private string $veterinarianClinic;
    private string $veterinarianName;
    private string $patientName;
    private string $examRequestUrl;


    /**
     * Create a new message instance.
     */
    public function __construct(
        string $veterinarianClinic,
        string $veterinarianName,
        string $patientName,
        string $examRequestUrl,
    ) {
        $this->veterinarianClinic = $veterinarianClinic;
        $this->veterinarianName = $veterinarianName;
        $this->patientName = $patientName;
        $this->examRequestUrl = $examRequestUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Requisição de Exame - Paciente ' . $this->veterinarianName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $mail = File::get(resource_path('templates/ExamRequestMail.html'));

        $mail = str_replace('{{ logoUrl }}', Storage::url('logos/logo-full-primary.png'), $mail);
        $mail = str_replace('{{ veterinarianClinic }}', $this->veterinarianClinic, $mail);
        $mail = str_replace('{{ veterinarianName }}', $this->veterinarianName, $mail);
        $mail = str_replace('{{ patientName }}', $this->patientName, $mail);

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
