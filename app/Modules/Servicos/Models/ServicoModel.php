<?php

namespace App\Modules\Servicos\Models;

use App\Modules\Core\Traits\AssignsMySqlAutoIncrementId;
use App\Modules\Core\Traits\BelongsToEmpresa;
use App\Modules\Core\Traits\HasEmpresaCompositeKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicoModel extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'servicos';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    protected $fillable = [
        'id_empresa',
        'id',
        'nome',
        'duracao_minutos',
        'preco',
        'status',
        'trinks_servico_id',
    ];

    protected function casts(): array
    {
        return [
            'duracao_minutos' => 'integer',
            'preco' => 'decimal:2',
        ];
    }
}
