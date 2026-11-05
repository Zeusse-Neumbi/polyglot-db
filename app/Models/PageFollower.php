<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PageFollower extends Pivot
{
    use HasFactory;

    protected $table = 'page_followers';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'user_id',
        'notifications_on',
        'followed_at',
    ];

    protected $casts = [
        'followed_at' => 'datetime',
        'notifications_on' => 'boolean',
    ];
}
