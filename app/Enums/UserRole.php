<?php

namespace App\Enums;

enum UserRole: string
{
    case Judge = 'judge';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Judge => 'Judge',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function canAccessAdminPanel(): bool
    {
        return in_array($this, [self::Admin, self::SuperAdmin]);
    }

    public function canManageEvents(): bool
    {
        return $this === self::SuperAdmin;
    }
}
