<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = ['user_id_1', 'user_id_2'];

    protected $fillable = [
        'user_id_1',
        'user_id_2',
        'status',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user1()
    {
        return $this->belongsTo(User::class, 'user_id_1');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user_id_2');
    }
}
