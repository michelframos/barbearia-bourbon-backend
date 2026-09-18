<?php

namespace App\Modules\Core\Traits;

use App\Modules\Core\Support\EmpresaContext;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Atribui o próximo `id` por id_empresa (simulando auto-increment em PK composta).
 */
trait AssignsMySqlAutoIncrementId
{
    public static function bootAssignsMySqlAutoIncrementId(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id_empresa)) {
                $model->id_empresa = EmpresaContext::id();
            }

            if ($model->id !== null && $model->id !== '') {
                return;
            }

            $query = static::query()->where('id_empresa', $model->id_empresa);

            if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
                $query->withTrashed();
            }

            $model->id = ((int) $query->max('id')) + 1;
        });
    }
}
