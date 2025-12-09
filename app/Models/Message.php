<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Message extends Model
{
    protected $connection = 'mongodb';
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachments',
        'is_read',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
