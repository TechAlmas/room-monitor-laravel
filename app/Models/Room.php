<?php

namespace App\Models;

use Auth;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class Room extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'rooms';

    protected $fillable = [
        'username', 'address'
    ];
    
}
