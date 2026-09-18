<?php

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Suporte a PK composta (id_empresa, id) no Eloquent.
 *
 * @property array<int, string> $primaryKey
 */
trait HasEmpresaCompositeKey
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();

        if (! is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getAttributeForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    protected function setKeysForSelectQuery($query)
    {
        $keys = $this->getKeyName();

        if (! is_array($keys)) {
            return parent::setKeysForSelectQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getAttributeForSaveQuery($keyName));
        }

        return $query;
    }

    protected function getAttributeForSaveQuery(string $keyName): mixed
    {
        if (array_key_exists($keyName, $this->original)) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }

    public function getKey(): mixed
    {
        $keys = $this->getKeyName();

        if (! is_array($keys)) {
            return $this->getAttribute($keys);
        }

        $composite = [];

        foreach ($keys as $key) {
            $composite[$key] = $this->getAttribute($key);
        }

        return $composite;
    }

    public function getKeyName(): array|string
    {
        return $this->primaryKey;
    }

    /**
     * Qualificador de coluna com PK composta (evita ambiguidade em joins).
     */
    public function getQualifiedKeyName(): string
    {
        $keys = $this->getKeyName();

        if (! is_array($keys)) {
            return $this->qualifyColumn($keys);
        }

        return $this->qualifyColumn($keys[0]);
    }
}
