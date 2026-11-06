<?php

namespace App\Modules\Trinks\Services;

use App\Modules\Trinks\Contracts\VerifiesTrinksSnsSignature;
use App\Modules\Trinks\Exceptions\TrinksApiException;
use Illuminate\Support\Facades\Http;

class TrinksSnsSignatureVerifier implements VerifiesTrinksSnsSignature
{
    public function assertValid(array $envelope): void
    {
        $certUrl = (string) ($envelope['SigningCertURL'] ?? '');
        $this->assertSnsUrl($certUrl, 'SigningCertURL');

        $signature = base64_decode((string) ($envelope['Signature'] ?? ''), true);
        if ($signature === false || $signature === '') {
            throw new TrinksApiException('Assinatura SNS ausente.');
        }

        $certificado = Http::timeout(15)->get($certUrl);
        if (! $certificado->successful()) {
            throw new TrinksApiException('Não foi possível obter o certificado SNS.');
        }

        $publico = openssl_pkey_get_public($certificado->body());
        if ($publico === false) {
            throw new TrinksApiException('Certificado SNS inválido.');
        }

        $algoritmo = ((string) ($envelope['SignatureVersion'] ?? '1')) === '2'
            ? OPENSSL_ALGO_SHA256
            : OPENSSL_ALGO_SHA1;

        $valido = openssl_verify($this->canonical($envelope), $signature, $publico, $algoritmo);

        if ($valido !== 1) {
            throw new TrinksApiException('Assinatura SNS inválida.');
        }
    }

    public function assertSnsUrl(string $url, string $campo): void
    {
        $partes = parse_url($url);
        $scheme = strtolower((string) ($partes['scheme'] ?? ''));
        $host = strtolower((string) ($partes['host'] ?? ''));

        $hostValido = (bool) preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com(\.cn)?$/', $host);

        if ($scheme !== 'https' || ! $hostValido) {
            throw new TrinksApiException("URL SNS inválida em {$campo}.");
        }
    }

    /**
     * @param  array<string, mixed>  $envelope
     */
    private function canonical(array $envelope): string
    {
        $tipo = (string) ($envelope['Type'] ?? '');
        $chaves = $tipo === 'Notification'
            ? array_values(array_filter([
                'Message',
                'MessageId',
                array_key_exists('Subject', $envelope) ? 'Subject' : null,
                'Timestamp',
                'TopicArn',
                'Type',
            ]))
            : ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

        $texto = '';

        foreach ($chaves as $chave) {
            if (! array_key_exists($chave, $envelope)) {
                continue;
            }

            $texto .= $chave."\n".$envelope[$chave]."\n";
        }

        return $texto;
    }
}
