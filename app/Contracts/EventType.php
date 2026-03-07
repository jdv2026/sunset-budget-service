<?php

namespace App\Contracts;

enum EventType: string {
    case LOGIN = 'LOGIN';
    case LOGOUT = 'LOGOUT';
    case CREATE = 'CREATE';
	case UPDATE = 'UPDATE';
	case DELETE      = 'DELETE';
	case ENABLE_2FA  = 'ENABLE_2FA';
	case DISABLE_2FA = 'DISABLE_2FA';

    /**
     * Get all values as array for validation or DB usage.
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
