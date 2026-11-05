<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redis;
use MongoDB\Laravel\Eloquent\Model;

class Post extends Model
{
    protected $connection = 'mongodb';

    use HasFactory, SoftDeletes;

    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = 3600;

    protected $fillable = [
        'author_id',
        'author_type',
        'content',
        'media_url',
        'media_type',
        'visibility',
        'likes_count',
        'comments_count',
    ];

    public function author()
    {
        return $this->morphTo();
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
        $count = Redis::get($key);

        if ($count === null) {
            $count = $this->likes_count ?? 0;
            Redis::setex($key, self::CACHE_TTL, $count);
        }

        return (int) $count;
    }

    /**
     * Get the comments count from Redis or database.
     */
    public function getCachedCommentsCountAttribute()
    {
        $key = "post:{$this->id}:comments_count";
        $count = Redis::get($key);

        if ($count === null) {
            $count = $this->comments_count ?? 0;
            Redis::setex($key, self::CACHE_TTL, $count);
        }

        return (int) $count;
    }

    /**
     * Increment the Redis likes count (and database for persistence).
     * Also refreshes the cache TTL.
     */
    public function incrementLikesCount()
    {
        $key = "post:{$this->id}:likes_count";
        Redis::incr($key);
        Redis::expire($key, self::CACHE_TTL);
        $this->increment('likes_count');
    }

    /**
     * Decrement the Redis likes count (and database for persistence).
     * Also refreshes the cache TTL.
     */
    public function decrementLikesCount()
    {
        $key = "post:{$this->id}:likes_count";
        Redis::decr($key);
        Redis::expire($key, self::CACHE_TTL);
        $this->decrement('likes_count');
    }

    /**
     * Increment the Redis comments count (and database for persistence).
     * Also refreshes the cache TTL.
     */
    public function incrementCommentsCount()
    {
        $key = "post:{$this->id}:comments_count";
        Redis::incr($key);
        Redis::expire($key, self::CACHE_TTL);
        $this->increment('comments_count');
    }

    /**
     * Decrement the Redis comments count (and database for persistence).
     * Also refreshes the cache TTL.
     */
    public function decrementCommentsCount()
    {
        $key = "post:{$this->id}:comments_count";
        Redis::decr($key);
        Redis::expire($key, self::CACHE_TTL);
        $this->decrement('comments_count');
    }
}
