<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlacklistedToken extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'blacklisted_tokens';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'jti',
        'expires_at',
    ];
}
