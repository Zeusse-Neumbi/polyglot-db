<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    protected $connection = 'mongodb';
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
        'media_url',
        'media_type',
        'visibility',
        'likes_count',
        'comments_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Get the likes count from Redis or database.
     */
    public function getCachedLikesCountAttribute()
    {
        $key = "post:{$this->id}:likes_count";
        $count = \Illuminate\Support\Facades\Redis::get($key);

        if ($count === null) {
            $count = $this->likes_count ?? 0;
            \Illuminate\Support\Facades\Redis::set($key, $count);
        }

        return (int) $count;
    }

    /**
     * Get the comments count from Redis or database.
     */
    public function getCachedCommentsCountAttribute()
    {
        $key = "post:{$this->id}:comments_count";
        $count = \Illuminate\Support\Facades\Redis::get($key);

        if ($count === null) {
            $count = $this->comments_count ?? 0;
            \Illuminate\Support\Facades\Redis::set($key, $count);
        }

        return (int) $count;
    }

    /**
     * Increment the Redis likes count (and database for persistence).
     */
    public function incrementLikesCount()
    {
        \Illuminate\Support\Facades\Redis::incr("post:{$this->id}:likes_count");
        $this->increment('likes_count');
    }

    /**
     * Decrement the Redis likes count (and database for persistence).
     */
    public function decrementLikesCount()
    {
        \Illuminate\Support\Facades\Redis::decr("post:{$this->id}:likes_count");
        $this->decrement('likes_count');
    }

    /**
     * Increment the Redis comments count (and database for persistence).
     */
    public function incrementCommentsCount()
    {
        \Illuminate\Support\Facades\Redis::incr("post:{$this->id}:comments_count");
        $this->increment('comments_count');
    }

    /**
     * Decrement the Redis comments count (and database for persistence).
     */
    public function decrementCommentsCount()
    {
        \Illuminate\Support\Facades\Redis::decr("post:{$this->id}:comments_count");
        $this->decrement('comments_count');
    }
}
