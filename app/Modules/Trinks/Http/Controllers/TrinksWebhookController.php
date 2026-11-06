<?php

namespace App\Modules\Trinks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Trinks\Contracts\VerifiesTrinksSnsSignature;
use App\Modules\Trinks\Exceptions\TrinksApiException;
use App\Modules\Trinks\Models\TrinksWebhookEvento;
use App\Modules\Trinks\Services\TrinksSnsSignatureVerifier;
use App\Modules\Trinks\UseCases\AplicarEventoAgendamentoTrinksUseCase;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TrinksWebhookController extends Controller
{
    public function __construct(
        private readonly VerifiesTrinksSnsSignature $verifier,
        private readonly TrinksSnsSignatureVerifier $urls,
        private readonly AplicarEventoAgendamentoTrinksUseCase $aplicar,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $envelope = $request->json()->all();
        $tipo = (string) ($envelope['Type'] ?? '');

        if (! in_array($tipo, ['SubscriptionConfirmation', 'Notification'], true)) {
            return response()->json(['ok' => true]);
        }

        try {
            $this->verifier->assertValid($envelope);
        } catch (TrinksApiException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        if ($tipo === 'SubscriptionConfirmation') {
            $url = (string) ($envelope['SubscribeURL'] ?? '');
            $this->urls->assertSnsUrl($url, 'SubscribeURL');
            Http::timeout(15)->get($url);

            return response()->json(['ok' => true]);
        }

        $messageId = (string) ($envelope['MessageId'] ?? '');

        if ($messageId === '') {
            return response()->json(['ok' => true]);
        }

        if (TrinksWebhookEvento::query()->where('message_id', $messageId)->exists()) {
            return response()->json(['ok' => true]);
        }

        $bruto = $envelope['Message'] ?? '{}';
        $evento = is_array($bruto) ? $bruto : json_decode((string) $bruto, true);

        if (! is_array($evento)) {
            return response()->json(['ok' => true]);
        }

        $this->aplicar->execute($evento);

        try {
            TrinksWebhookEvento::query()->create([
                'message_id' => $messageId,
                'tipo_evento' => isset($evento['TipoDeEvento']) ? (int) $evento['TipoDeEvento'] : null,
                'id_agendamento_trinks' => isset($evento['IdDoAgendamento']) ? (int) $evento['IdDoAgendamento'] : null,
            ]);
        } catch (QueryException $exception) {
            if (! $this->duplicado($exception)) {
                throw $exception;
            }
        }

        return response()->json(['ok' => true]);
    }

    private function duplicado(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return $sqlState === '23000';
    }
}
