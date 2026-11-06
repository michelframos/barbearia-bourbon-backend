<?php

namespace App\Modules\Trinks\Services;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Servicos\Models\ServicoModel;
use App\Modules\Trinks\Exceptions\TrinksApiException;
use App\Modules\Trinks\Support\TrinksTexto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrinksVinculoService
{
    public function __construct(private readonly TrinksHttpClient $client) {}

    public function clienteIdParaEnvio(ClienteModel $cliente): int
    {
        if (filled($cliente->trinks_cliente_id)) {
            return (int) $cliente->trinks_cliente_id;
        }

        $digitos = TrinksTexto::digitos($cliente->telefone);

        if (strlen($digitos) >= 10) {
            $porTelefone = $this->clienteRemotoUnico(
                $this->client->listarClientes(['Telefone' => $digitos]),
                $cliente->nome,
                $digitos
            );

            if ($porTelefone !== null) {
                $this->salvarId($cliente, 'trinks_cliente_id', (int) $porTelefone['id']);

                return (int) $porTelefone['id'];
            }
        }

        $porNome = $this->clienteRemotoUnico(
            $this->client->listarClientes(['Nome' => $cliente->nome]),
            $cliente->nome,
            null
        );

        if ($porNome !== null) {
            $this->salvarId($cliente, 'trinks_cliente_id', (int) $porNome['id']);

            return (int) $porNome['id'];
        }

        if (strlen($digitos) < 10) {
            throw new TrinksApiException(
                'O cliente "'.$cliente->nome.'" precisa de um telefone com DDD para ser cadastrado na Trinks.'
            );
        }

        $id = $this->client->criarCliente([
            'nome' => $cliente->nome,
            'codigoExterno' => mb_substr((string) $cliente->id, 0, 15),
            'telefones' => [[
                'ddd' => substr($digitos, 0, 2),
                'numero' => substr($digitos, 2),
                'tipoId' => 3,
            ]],
        ]);

        $this->salvarId($cliente, 'trinks_cliente_id', $id);

        return $id;
    }

    public function profissionalIdParaEnvio(?BarbeiroModel $barbeiro): ?int
    {
        if ($barbeiro === null) {
            return null;
        }

        if (filled($barbeiro->trinks_profissional_id)) {
            return (int) $barbeiro->trinks_profissional_id;
        }

        $id = $this->idProfissionalPorNome($barbeiro->nome, 'profissional');
        $this->salvarId($barbeiro, 'trinks_profissional_id', $id);

        return $id;
    }

    public function servicoIdParaEnvio(ServicoModel $servico): int
    {
        if (filled($servico->trinks_servico_id)) {
            return (int) $servico->trinks_servico_id;
        }

        $alvo = TrinksTexto::nome($servico->nome);
        $matches = collect($this->client->listarServicos())
            ->filter(fn (array $item) => TrinksTexto::nome($item['nome'] ?? '') === $alvo)
            ->unique('id')
            ->values();

        if ($matches->count() > 1) {
            throw new TrinksApiException('Há mais de um serviço na Trinks com o nome "'.$servico->nome.'".');
        }

        $encontrado = $matches->first();

        if ($encontrado === null) {
            throw new TrinksApiException(
                'Cadastre o serviço "'.$servico->nome.'" na Trinks com o mesmo nome para sincronizar este agendamento.'
            );
        }

        $id = (int) $encontrado['id'];
        $this->salvarId($servico, 'trinks_servico_id', $id);

        return $id;
    }

    public function assistenteIdParaEnvio(?AssistenteModel $assistente): ?int
    {
        if ($assistente === null) {
            return null;
        }

        if (filled($assistente->trinks_profissional_id)) {
            return (int) $assistente->trinks_profissional_id;
        }

        try {
            $id = $this->idProfissionalPorNome($assistente->nome, 'assistente');
        } catch (TrinksApiException) {
            return null;
        }

        $this->salvarId($assistente, 'trinks_profissional_id', $id);

        return $id;
    }

    public function clienteLocal(int $idEmpresa, int $trinksId, string $nome, ?string $telefone): ClienteModel
    {
        $existente = ClienteModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('trinks_cliente_id', $trinksId)
            ->first();

        if ($existente) {
            return $existente;
        }

        $clientes = ClienteModel::query()->where('id_empresa', $idEmpresa)->get();
        $digitos = TrinksTexto::digitos($telefone);

        if (strlen($digitos) >= 10) {
            $porTelefone = $clientes->filter(
                fn (ClienteModel $cliente) => TrinksTexto::digitos($cliente->telefone) === $digitos
                    && ($cliente->trinks_cliente_id === null || (int) $cliente->trinks_cliente_id === $trinksId)
            );

            if ($porTelefone->count() === 1) {
                $cliente = $porTelefone->first();
                $this->salvarId($cliente, 'trinks_cliente_id', $trinksId);

                return $cliente;
            }

            if ($porTelefone->count() > 1) {
                throw new TrinksApiException('Há mais de um cliente local com o telefone informado.');
            }
        }

        $porNome = $this->unicoLocalPorNome($clientes, $nome, 'trinks_cliente_id', 'cliente');

        if ($porNome instanceof ClienteModel) {
            $this->salvarId($porNome, 'trinks_cliente_id', $trinksId);

            return $porNome;
        }

        return ClienteModel::query()->create([
            'id_empresa' => $idEmpresa,
            'nome' => $nome !== '' ? $nome : 'Cliente '.$trinksId,
            'telefone' => $telefone,
            'status' => 'a',
            'trinks_cliente_id' => $trinksId,
        ]);
    }

    public function barbeiroLocal(int $idEmpresa, ?int $trinksId, ?string $nome): ?BarbeiroModel
    {
        if ($trinksId === null || $trinksId <= 0) {
            return null;
        }

        $existente = BarbeiroModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('trinks_profissional_id', $trinksId)
            ->first();

        if ($existente) {
            return $existente;
        }

        $nomeResolvido = filled($nome) ? $nome : $this->nomeProfissionalRemoto($trinksId);

        $porNome = $this->unicoLocalPorNome(
            BarbeiroModel::query()->where('id_empresa', $idEmpresa)->get(),
            $nomeResolvido,
            'trinks_profissional_id',
            'barbeiro'
        );

        if ($porNome instanceof BarbeiroModel) {
            $this->salvarId($porNome, 'trinks_profissional_id', $trinksId);

            return $porNome;
        }

        return BarbeiroModel::query()->create([
            'id_empresa' => $idEmpresa,
            'nome' => $nomeResolvido,
            'status_operacional' => 'offline',
            'status' => 'a',
            'trinks_profissional_id' => $trinksId,
        ]);
    }

    public function servicoLocal(
        int $idEmpresa,
        int $trinksId,
        string $nome,
        int $duracao,
        float $preco,
        bool $atualizarPreco = false,
    ): ServicoModel {
        $existente = ServicoModel::query()
            ->where('id_empresa', $idEmpresa)
            ->where('trinks_servico_id', $trinksId)
            ->first();

        if ($existente) {
            if ($atualizarPreco) {
                $existente->update([
                    'duracao_minutos' => max(1, min(9999, $duracao)),
                    'preco' => max(0, $preco),
                ]);
            }

            return $existente;
        }

        $porNome = $this->unicoLocalPorNome(
            ServicoModel::query()->where('id_empresa', $idEmpresa)->get(),
            $nome,
            'trinks_servico_id',
            'serviço'
        );

        if ($porNome instanceof ServicoModel) {
            $dados = ['trinks_servico_id' => $trinksId];

            if ($atualizarPreco) {
                $dados['duracao_minutos'] = max(1, min(9999, $duracao));
                $dados['preco'] = max(0, $preco);
            }

            $porNome->update($dados);

            return $porNome;
        }

        return ServicoModel::query()->create([
            'id_empresa' => $idEmpresa,
            'nome' => $nome !== '' ? $nome : 'Serviço '.$trinksId,
            'duracao_minutos' => max(1, min(9999, $duracao)),
            'preco' => max(0, $preco),
            'status' => 'a',
            'trinks_servico_id' => $trinksId,
        ]);
    }

    public function garantirBarbeiroServico(int $idEmpresa, ?int $idBarbeiro, int $idServico): void
    {
        if ($idBarbeiro === null) {
            return;
        }

        $existe = DB::table('barbeiro_servico')
            ->where('id_empresa', $idEmpresa)
            ->where('id_barbeiro', $idBarbeiro)
            ->where('id_servico', $idServico)
            ->exists();

        if ($existe) {
            return;
        }

        DB::table('barbeiro_servico')->insert([
            'id_empresa' => $idEmpresa,
            'id_barbeiro' => $idBarbeiro,
            'id_servico' => $idServico,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $clientes
     * @return array<string, mixed>|null
     */
    private function clienteRemotoUnico(array $clientes, string $nome, ?string $digitos): ?array
    {
        $alvo = TrinksTexto::nome($nome);
        $matches = collect($clientes)->filter(function (array $item) use ($alvo, $digitos) {
            if ($digitos !== null) {
                return $this->telefonesDoCliente($item)->contains($digitos);
            }

            return TrinksTexto::nome($item['nome'] ?? '') === $alvo;
        })->unique('id')->values();

        if ($matches->count() > 1) {
            throw new TrinksApiException('Há mais de um cliente na Trinks correspondente a "'.$nome.'".');
        }

        $encontrado = $matches->first();

        return is_array($encontrado) ? $encontrado : null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return Collection<int, string>
     */
    private function telefonesDoCliente(array $item): Collection
    {
        return collect($item['telefones'] ?? [])
            ->map(function ($telefone) {
                if (! is_array($telefone)) {
                    return '';
                }

                return TrinksTexto::digitos(($telefone['ddd'] ?? '').($telefone['telefone'] ?? ''));
            })
            ->filter()
            ->values();
    }

    private function idProfissionalPorNome(string $nome, string $rotulo): int
    {
        $alvo = TrinksTexto::nome($nome);
        $matches = collect($this->client->listarProfissionais())
            ->filter(function (array $item) use ($alvo) {
                return TrinksTexto::nome($item['nome'] ?? '') === $alvo
                    || ($alvo !== '' && TrinksTexto::nome($item['apelido'] ?? '') === $alvo);
            })
            ->unique('id')
            ->values();

        if ($matches->count() > 1) {
            throw new TrinksApiException('Há mais de um '.$rotulo.' na Trinks com o nome "'.$nome.'".');
        }

        $encontrado = $matches->first();

        if ($encontrado === null) {
            throw new TrinksApiException(
                'Cadastre o '.$rotulo.' "'.$nome.'" na Trinks com o mesmo nome para sincronizar este agendamento.'
            );
        }

        return (int) $encontrado['id'];
    }

    private function nomeProfissionalRemoto(int $trinksId): string
    {
        try {
            $encontrado = collect($this->client->listarProfissionais())
                ->first(fn (array $item) => (int) ($item['id'] ?? 0) === $trinksId);

            if (is_array($encontrado) && filled($encontrado['nome'] ?? null)) {
                return (string) $encontrado['nome'];
            }
        } catch (TrinksApiException) {
            // O agendamento local segue com um nome provisório.
        }

        return 'Profissional '.$trinksId;
    }

    /**
     * @param  Collection<int, ClienteModel|BarbeiroModel|ServicoModel>  $itens
     */
    private function unicoLocalPorNome(Collection $itens, string $nome, string $coluna, string $rotulo): ClienteModel|BarbeiroModel|ServicoModel|null
    {
        $alvo = TrinksTexto::nome($nome);

        if ($alvo === '') {
            return null;
        }

        $matches = $itens->filter(
            fn ($item) => TrinksTexto::nome($item->nome) === $alvo && $item->{$coluna} === null
        );

        if ($matches->count() > 1) {
            throw new TrinksApiException('Há mais de um '.$rotulo.' local com o nome "'.$nome.'".');
        }

        $encontrado = $matches->first();

        return $encontrado instanceof ClienteModel
            || $encontrado instanceof BarbeiroModel
            || $encontrado instanceof ServicoModel
            ? $encontrado
            : null;
    }

    private function salvarId(ClienteModel|BarbeiroModel|ServicoModel|AssistenteModel $model, string $coluna, int $id): void
    {
        if ((int) $model->{$coluna} === $id) {
            return;
        }

        $model->{$coluna} = $id;
        $model->save();
    }
}
