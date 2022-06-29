<?php

namespace App\Models;

use Auth;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class MasterAlarm extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'master_alarms';

    
}
