<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class SessionAudit extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'sessions_audit';

    protected $fillable = [
        'user_id',
        'session_token',
        'ip_address',
        'device',
        'user_agent',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];
}
