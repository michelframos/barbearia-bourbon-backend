<?php

namespace App\Modules\Trinks\Services;

use App\Modules\Core\Models\Empresa;
use App\Modules\Trinks\Exceptions\TrinksApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TrinksHttpClient
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $listas = [];

    public function __construct(private readonly Empresa $empresa) {}

    public static function para(Empresa $empresa): self
    {
        return new self($empresa);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function listarClientes(array $filtros): array
    {
        return $this->paginar('/v1/clientes', $filtros, 'Page', 'PageSize', 10);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarProfissionais(): array
    {
        return $this->lista('/v1/profissionais');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarServicos(): array
    {
        return $this->lista('/v1/servicos');
    }

    /**
     * @return array<string, mixed>
     */
    public function listarAgendamentos(int $page, string $dataInicio): array
    {
        $response = $this->send('GET', '/v1/agendamentos', [
            'page' => $page,
            'pageSize' => 50,
            'dataInicio' => $dataInicio,
        ]);
        $this->throwIfFailed($response);

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function criarCliente(array $payload): int
    {
        $response = $this->send('POST', '/v1/clientes', body: $payload);
        $this->throwIfFailed($response);

        return $this->idCriado($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function criarAgendamento(array $payload): int
    {
        $response = $this->send('POST', '/v1/agendamentos', body: $payload);
        $this->throwIfFailed($response);

        return $this->idCriado($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function editarAgendamento(int $id, array $payload): void
    {
        $response = $this->send('PUT', '/v1/agendamentos/'.$id, body: $payload);
        $this->throwIfFailed($response);
    }

    public function confirmarAgendamento(int $id): void
    {
        $response = $this->send('PATCH', '/v1/agendamentos/'.$id.'/status/confirmado');
        $this->throwIfFailed($response);
    }

    public function cancelarAgendamento(int $id): void
    {
        $response = $this->send('PATCH', '/v1/agendamentos/'.$id.'/status/cancelado', body: [
            'quemCancelou' => 3,
            'motivo' => 'Cancelado pelo estabelecimento',
        ]);

        if ($response->status() === 400 && str_contains(mb_strtolower($this->mensagem($response)), 'cancel')) {
            return;
        }

        $this->throwIfFailed($response);
    }

    public function removerAssistente(int $id): void
    {
        $response = $this->send('DELETE', '/v1/agendamentos/'.$id.'/assistente');

        if (in_array($response->status(), [400, 404], true)) {
            return;
        }

        $this->throwIfFailed($response);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lista(string $path): array
    {
        if (! array_key_exists($path, $this->listas)) {
            $this->listas[$path] = $this->paginar($path, [], 'page', 'pageSize', 200);
        }

        return $this->listas[$path];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    private function paginar(string $path, array $query, string $pageKey, string $sizeKey, int $limitePaginas): array
    {
        $page = 1;
        $itens = [];

        do {
            $response = $this->send('GET', $path, array_merge($query, [
                $pageKey => $page,
                $sizeKey => 50,
            ]));
            $this->throwIfFailed($response);
            $json = $response->json() ?? [];

            foreach ($json['data'] ?? [] as $item) {
                if (is_array($item)) {
                    $itens[] = $item;
                }
            }

            $totalPages = max(1, (int) ($json['totalPages'] ?? 1));
            $page++;
        } while ($page <= $totalPages && $page <= $limitePaginas);

        return $itens;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     */
    private function send(string $method, string $path, array $query = [], ?array $body = null): Response
    {
        $pending = Http::baseUrl($this->baseUrl())
            ->withHeaders([
                'X-Api-Key' => (string) $this->empresa->trinks_api_key,
                'estabelecimentoId' => (string) $this->empresa->trinks_estabelecimento_id,
                'Accept' => 'application/json',
            ])
            ->acceptJson()
            ->asJson()
            ->timeout(30);

        return match (strtoupper($method)) {
            'GET' => $pending->get($path, $query),
            'POST' => $pending->post($path, $body ?? []),
            'PUT' => $pending->put($path, $body ?? []),
            'PATCH' => $pending->patch($path, $body ?? []),
            'DELETE' => $pending->delete($path, $body ?? []),
            default => throw new TrinksApiException('Método HTTP não suportado.'),
        };
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.trinks.base_url', 'https://api.trinks.com'), '/');
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $mensagem = $response->status() === 401 || $response->status() === 403
            ? 'A chave da API da Trinks foi recusada.'
            : $this->mensagem($response);

        throw new TrinksApiException($mensagem, $response->status());
    }

    private function mensagem(Response $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            $partes = [];

            foreach ($json['Errors'] ?? [] as $erro) {
                if (is_array($erro) && filled($erro['ErrorMessage'] ?? null)) {
                    $partes[] = (string) $erro['ErrorMessage'];
                }
            }

            if ($partes !== []) {
                return implode(' ', $partes);
            }

            if (filled($json['Message'] ?? null)) {
                return (string) $json['Message'];
            }
        }

        return 'Falha na comunicação com a Trinks.';
    }

    private function idCriado(Response $response): int
    {
        $id = $response->json('id');

        if (! filled($id)) {
            throw new TrinksApiException('A Trinks não devolveu o identificador criado.');
        }

        return (int) $id;
    }
}
