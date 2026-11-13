<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Módulos
    App\Modules\Atendimentos\Providers\AtendimentoServiceProvider::class,
    App\Modules\Agendamentos\Providers\AgendamentoServiceProvider::class,
    App\Modules\Clientes\Providers\ClienteServiceProvider::class,
    App\Modules\Barbeiros\Providers\BarbeiroServiceProvider::class,
    App\Modules\Core\Providers\CoreServiceProvider::class,
    App\Modules\Auth\Providers\AuthServiceProvider::class,
    App\Modules\Assistentes\Providers\AssistenteServiceProvider::class,
    App\Modules\Servicos\Providers\ServicoServiceProvider::class,
    App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
    App\Modules\Trinks\Providers\TrinksServiceProvider::class,
    App\Modules\Usuarios\Providers\UsuariosServiceProvider::class,
];
