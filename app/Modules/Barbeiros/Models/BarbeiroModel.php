<?php

namespace App\Modules\Barbeiros\Models;

use App\Modules\Core\Traits\AssignsMySqlAutoIncrementId;
use App\Modules\Core\Traits\BelongsToEmpresa;
use App\Modules\Core\Traits\HasEmpresaCompositeKey;
use App\Modules\Servicos\Models\ServicoModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarbeiroModel extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'barbeiros';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    protected $fillable = [
        'id_empresa',
        'id',
        'nome',
        'telefone',
        'foto',
        'status_operacional',
        'hora_entrada',
        'status',
    ];

    /**
     * N:N com serviços via pivot barbeiro_servico.
     * Filtra por id_empresa no pivot e na tabela de serviços.
     */
    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(
            ServicoModel::class,
            'barbeiro_servico',
            'id_barbeiro',
            'id_servico'
        )
            ->wherePivot('id_empresa', $this->id_empresa)
            ->where('servicos.id_empresa', $this->id_empresa)
            ->withPivot('id_empresa');
    }
}
