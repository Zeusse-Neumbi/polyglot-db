<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\HybridRelations;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HybridRelations;

    protected $fillable = [
        'username',
        'email',
        'password',
        'nom',
        'prenom',
        'date_naissance',
        'genre',
        'bio',
        'profile_photo',
        'cover_photo',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_naissance' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    public function friends1()
    {
        return $this->hasMany(Friend::class, 'user_id_1');
    }

    public function friends2()
    {
        return $this->hasMany(Friend::class, 'user_id_2');
    }

    public function friends()
    {
        // Complex relationship, maybe simpler via accessor
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('role', 'joined_at');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withPivot('role', 'joined_at');
    }

    public function pages()
    {
        return $this->hasMany(Page::class, 'owner_id');
    }

    public function followedPages()
    {
        return $this->belongsToMany(Page::class, 'page_followers')
            ->withPivot('notifications_on', 'followed_at');
    }
}
