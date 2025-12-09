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
}
