<?php

namespace App\Models;

use Auth;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class AlarmImport extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'alarms_import';

    protected $fillable = [
        'user_id', 'hour', 'types_of_alarms','room_name','user','alarms','agent_sent','agent_name','guest_reached','guest_name'
    ];

    
}
