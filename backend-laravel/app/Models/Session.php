<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Session extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $primaryKey = 'session_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'user_agent',
        'refresh_token',
        'last_login',
    ];

    protected static function boot()
    {
        parent::boot();
    
        static::creating(function ($model) {
            if (empty($model->session_id)) {
                $model->session_id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }
}
