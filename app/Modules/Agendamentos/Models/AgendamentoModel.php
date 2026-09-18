<?php

namespace App\Modules\Agendamentos\Models;

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

class AgendamentoModel extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;
    use HasFactory;
    use SoftDeletes;

    public const STATUS_AGENDADO = 'agendado';

    public const STATUS_CONFIRMADO = 'confirmado';

    public const STATUS_EM_ATENDIMENTO = 'em_atendimento';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS_NAO_COMPARECEU = 'nao_compareceu';

    /** Status que ocupam o slot do barbeiro. */
    public const STATUS_BLOQUEANTES = [
        self::STATUS_AGENDADO,
        self::STATUS_CONFIRMADO,
        self::STATUS_EM_ATENDIMENTO,
    ];

    protected $table = 'agendamentos';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    protected $fillable = [
        'id_empresa',
        'id',
        'data',
        'hora',
        'id_cliente',
        'id_assistente',
        'id_barbeiro',
        'id_servico',
        'observacoes',
        'status_agenda',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
        ];
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
