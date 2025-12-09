<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationUser extends Pivot
{
    use HasFactory;

    protected $table = 'conversation_user';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'joined_at',
        'role',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];
}
