<?php

namespace App\Modules\Core\Models;

use App\Models\User;
use App\Modules\Core\Traits\AssignsMySqlAutoIncrementId;
use App\Modules\Core\Traits\BelongsToEmpresa;
use App\Modules\Core\Traits\HasEmpresaCompositeKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use AssignsMySqlAutoIncrementId;
    use BelongsToEmpresa;
    use HasEmpresaCompositeKey;

    protected $table = 'audit_logs';

    /** @var array<int, string> */
    protected $primaryKey = ['id_empresa', 'id'];

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'id',
        'id_user',
        'acao',
        'entidade',
        'id_entidade',
        'descricao',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    protected static function booted(): void
    {
        static::creating(function (AuditLog $model): void {
            if (! $model->created_at) {
                $model->created_at = now();
            }
        });
    }
}
