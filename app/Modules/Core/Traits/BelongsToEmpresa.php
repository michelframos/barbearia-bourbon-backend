<?php

namespace App\Modules\Core\Traits;

use App\Modules\Core\Models\Empresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relação com a empresa única da instalação.
 *
 * @property int|null $id_empresa
 */
trait BelongsToEmpresa
{
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id');
    }
}
