<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Friend extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'friends';

    /**
     * Indicates if the model should be timestamped.
     * We only have created_at, no updated_at.
     */
    const UPDATED_AT = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The primary key is a composite of two columns.
     * Note: Laravel doesn't natively support composite PKs,
     * so we override key methods below.
     */
    protected $primaryKey = ['user_id_1', 'user_id_2'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id_1',
        'user_id_2',
        'status',
        'accepted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the primary key for the model.
     * Returns null since we have a composite key.
     */
    public function getKeyName()
    {
        return null;
    }

    /**
     * Set the keys for a save/update query.
     * This override allows Eloquent to properly handle composite PKs.
     */
    protected function setKeysForSaveQuery($query): Builder
    {
        $query->where('user_id_1', '=', $this->getAttribute('user_id_1'))
            ->where('user_id_2', '=', $this->getAttribute('user_id_2'));

        return $query;
    }

    /**
     * Get the primary key value for routing.
     * Returns a combined key for URL generation.
     */
    public function getRouteKey(): string
    {
        return $this->getAttribute('user_id_1') . '-' . $this->getAttribute('user_id_2');
    }

    /**
     * Resolve the route binding for the composite key.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $keys = explode('-', $value);

        if (count($keys) !== 2) {
            return null;
        }

        return $this->where('user_id_1', $keys[0])
            ->where('user_id_2', $keys[1])
            ->first();
    }

    // Relationships

    public function user1()
    {
        return $this->belongsTo(User::class, 'user_id_1');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user_id_2');
    }
}
