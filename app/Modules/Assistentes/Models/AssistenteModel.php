<?php

namespace App\Modules\Assistentes\Models;

use App\Modules\Core\Traits\AssignsMySqlAutoIncrementId;
use App\Modules\Core\Traits\BelongsToEmpresa;
use App\Modules\Core\Traits\HasEmpresaCompositeKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AssistenteModel extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'assistentes';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    protected $fillable = [
        'id_empresa',
        'id',
        'nome',
        'telefone',
        'turno',
        'ordem_sequencial',
        'hora_checkin',
        'status_operacional',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hora_checkin' => 'datetime',
            'ordem_sequencial' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (AssistenteModel $model): void {
            if ($model->ordem_sequencial === null) {
                $model->updateQuietly(['ordem_sequencial' => (int) $model->id]);
            }
        });
    }

    /**
     * @return list<array{id_barbeiro: int, dia_semana: int}>
     */
    public function vinculosBarbeiroDia(): array
    {
        return DB::table('assistente_barbeiro_dia')
            ->where('id_empresa', $this->id_empresa)
            ->where('id_assistente', $this->id)
            ->orderBy('dia_semana')
            ->orderBy('id_barbeiro')
            ->get(['id_barbeiro', 'dia_semana'])
            ->map(fn ($row) => [
                'id_barbeiro' => (int) $row->id_barbeiro,
                'dia_semana' => (int) $row->dia_semana,
            ])
            ->values()
            ->all();
    }
}
