<?php

namespace App\Modules\Auth\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperacaoSenhaMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $resetUrl;

    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $this->resetUrl = $frontendUrl.'/redefinir-senha?'.http_build_query([
            'token' => $this->token,
            'email' => $this->user->email,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperação de senha — Bourbon Barbearia',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recuperacao-senha',
            with: [
                'userName' => $this->user->name,
                'resetUrl' => $this->resetUrl,
                'expiresHours' => 4,
            ],
        );
    }
}
