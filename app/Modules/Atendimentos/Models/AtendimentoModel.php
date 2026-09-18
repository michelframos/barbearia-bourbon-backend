<?php

namespace App\Modules\Atendimentos\Models;

use App\Modules\Agendamentos\Models\AgendamentoModel;
use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Barbeiros\Models\BarbeiroModel;
use App\Modules\Clientes\Models\ClienteModel;
use App\Modules\Core\Traits\AssignsMySqlAutoIncrementId;
use App\Modules\Core\Traits\BelongsToEmpresa;
use App\Modules\Core\Traits\HasEmpresaCompositeKey;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AtendimentoModel extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AGUARDANDO = 'aguardando';

    public const STATUS_CHAMADO = 'chamado';

    public const STATUS_EM_ATENDIMENTO = 'em_atendimento';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_CANCELADO = 'cancelado';

    /** Status que ocupam posição na fila da assistente. */
    public const STATUS_ABERTOS = [
        self::STATUS_AGUARDANDO,
        self::STATUS_CHAMADO,
        self::STATUS_EM_ATENDIMENTO,
    ];

    protected $table = 'atendimentos';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    protected $fillable = [
        'id_empresa',
        'id',
        'id_agendamento',
        'id_cliente',
        'id_assistente',
        'id_barbeiro',
        'id_servico',
        'posicao_fila',
        'data',
        'hora_chegada',
        'hora_inicio',
        'hora_fim',
        'status_atendimento',
        'observacoes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'posicao_fila' => 'integer',
        ];
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(AgendamentoModel::class, 'id_agendamento', 'id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteModel::class, 'id_cliente', 'id');
    }

    public function assistente(): BelongsTo
    {
        return $this->belongsTo(AssistenteModel::class, 'id_assistente', 'id');
    }

    public function barbeiro(): BelongsTo
    {
        return $this->belongsTo(BarbeiroModel::class, 'id_barbeiro', 'id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(ServicoModel::class, 'id_servico', 'id');
    }
}
