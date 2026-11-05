<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Reaction extends Model
{
    protected $connection = 'mongodb';

    use HasFactory;

    protected static function booted()
    {
        static::created(function ($reaction) {
            if ($reaction->post) {
                $reaction->post->incrementLikesCount();
            }
        });

        static::deleted(function ($reaction) {
            if ($reaction->post) {
                $reaction->post->decrementLikesCount();
            }
        });
    }

    protected $fillable = [
        'post_id',
        'user_id',
        'message_id',
        'reaction_type',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
