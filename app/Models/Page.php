<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    const UPDATED_AT = null;


    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'category',
        'avatar',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'page_followers')
            ->withPivot('notifications_on', 'followed_at');
    }
}
