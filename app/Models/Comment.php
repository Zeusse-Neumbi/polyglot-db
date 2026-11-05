<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

class Comment extends Model
{
    protected $connection = 'mongodb';

    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::created(function ($comment) {
            if ($comment->post) {
                $comment->post->incrementCommentsCount();
            }
        });

        static::deleted(function ($comment) {
            if ($comment->post) {
                $comment->post->decrementCommentsCount();
            }
        });

        static::restored(function ($comment) {
            if ($comment->post) {
                $comment->post->incrementCommentsCount();
            }
        });
    }

    protected $fillable = [
        'post_id',
        'user_id',
        'content',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
