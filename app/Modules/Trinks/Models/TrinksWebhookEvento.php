<?php

namespace App\Modules\Trinks\Models;

use Illuminate\Database\Eloquent\Model;

class TrinksWebhookEvento extends Model
{
    protected $table = 'trinks_webhook_eventos';

    protected $fillable = [
        'message_id',
        'tipo_evento',
        'id_agendamento_trinks',
    ];
}
