<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Empresa única da instalação
    |--------------------------------------------------------------------------
    |
    | Sistema single-company: id_empresa é fixo (compatível com make:module).
    | Não há multi-tenant SaaS nem troca dinâmica de empresa.
    |
    */

    'id' => (int) env('EMPRESA_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Particionamento MySQL (opcional)
    |--------------------------------------------------------------------------
    |
    | Quando true e o driver for mysql, TenantTable aplica
    | PARTITION BY KEY(id_empresa) após o CREATE.
    |
    */

    'mysql_partitioning_enabled' => (bool) env('MYSQL_PARTITIONING_ENABLED', false),

    'mysql_partition_count' => (int) env('MYSQL_PARTITION_COUNT', 8),

];
