<?php

namespace App\Models;

use App\Contracts\UserType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'password',
        'type',
        'jti',
        'attempts',
        'attempts_expiry',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_attempts',
        'two_factor_attempts_expiry',
    ];

    protected $hidden = [
        'password',
        'jti',
        'two_factor_secret',
    ];

    protected $casts = [
        'attempts_expiry'            => 'datetime',
        'attempts'                   => 'integer',
        'type'                       => UserType::class,
        'two_factor_enabled'         => 'boolean',
        'two_factor_attempts'        => 'integer',
        'two_factor_attempts_expiry' => 'datetime',
    ];

    public function recoveryCodes(): HasMany
    {
        return $this->hasMany(RecoveryCode::class);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
