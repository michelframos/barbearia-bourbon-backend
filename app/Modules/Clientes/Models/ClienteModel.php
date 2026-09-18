<?php

namespace App\Modules\Clientes\Models;

use App\Modules\Assistentes\Models\AssistenteModel;
use App\Modules\Core\Traits\AssignsMySqlAutoIncrementId;
use App\Modules\Core\Traits\BelongsToEmpresa;
use App\Modules\Core\Traits\HasEmpresaCompositeKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteModel extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'clientes';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    protected $fillable = [
        'id_empresa',
        'id',
        'nome',
        'telefone',
        'id_assistente_preferida',
        'observacoes',
        'status',
    ];

    public function assistentePreferida(): BelongsTo
    {
        return $this->belongsTo(AssistenteModel::class, 'id_assistente_preferida', 'id');
    }
}
