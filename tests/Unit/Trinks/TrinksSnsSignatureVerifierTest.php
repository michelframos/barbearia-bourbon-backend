<?php

namespace Tests\Unit\Trinks;

use App\Modules\Trinks\Exceptions\TrinksApiException;
use App\Modules\Trinks\Services\TrinksSnsSignatureVerifier;
use Tests\TestCase;

class TrinksSnsSignatureVerifierTest extends TestCase
{
    public function test_rejeita_certificado_fora_da_aws(): void
    {
        $this->expectException(TrinksApiException::class);

        (new TrinksSnsSignatureVerifier)->assertValid([
            'Type' => 'Notification',
            'SigningCertURL' => 'https://evil.example/cert.pem',
            'Signature' => base64_encode('abc'),
            'SignatureVersion' => '1',
            'Message' => '{}',
            'MessageId' => '1',
            'Timestamp' => '2026-11-05T12:00:00.000Z',
            'TopicArn' => 'arn:aws:sns:us-east-1:1:topico',
        ]);
    }
}
