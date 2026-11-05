<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'avatar',
        'visibility',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('role', 'joined_at');
    }
}
