<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\HybridRelations;

class Conversation extends Model
{
    use HasFactory, HybridRelations;

    const UPDATED_AT = null;

    protected $fillable = ['type'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('role', 'joined_at');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
