<?php

namespace App\Support;

use App\Models\User;

/**
 * WhatsApp operator types reuse the existing user roles so the CRM keeps a
 * single source of identity. "sales_rep" is the common operator.
 */
final class OperatorRole
{
    public const ADMIN = 'admin';

    public const MANAGER = 'manager';

    public const OPERATOR = 'sales_rep';

    public const LABELS = [
        self::ADMIN => 'Administrador',
        self::MANAGER => 'Gerente',
        self::OPERATOR => 'Operador',
    ];

    /** @return array<string, string> */
    public static function labels(): array
    {
        return self::LABELS;
    }

    public static function isManager(User $user): bool
    {
        return $user->is_active && in_array($user->role, [self::ADMIN, self::MANAGER], true);
    }

    public static function canViewAllConversations(User $user): bool
    {
        return self::isManager($user);
    }

    public static function isOperator(User $user): bool
    {
        return array_key_exists($user->role, self::LABELS) && $user->is_active;
    }
}
