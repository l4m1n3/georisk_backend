<?php

namespace App\Enums;

enum UserRole: string
{
    case Citizen = 'citizen';
    case Validator = 'validator';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Citoyen',
            self::Validator => 'Validateur',
        };
    }
}
