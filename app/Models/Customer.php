<?php

namespace App\Models;

use Auth;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
class Customer extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'customers';

    protected $fillable = [
        'company_name', 'alias', 'date', 'vat', 'iban', 'origin', 'gocardless_id', 'accounting_id', 'subscription', 'contact', 'username', 'phone_number', 'billing_email','reports_email','status'
    ];

    
}
