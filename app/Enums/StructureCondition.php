<?php

namespace App\Enums;

enum StructureCondition: string
{
    case Functional = 'functional';
    case PartiallyObstructed = 'partially_obstructed';
    case Obstructed = 'obstructed';
    case Destroyed = 'destroyed';

    public function label(): string
    {
        return match ($this) {
            self::Functional => 'Fonctionnel',
            self::PartiallyObstructed => 'Partiellement obstrué',
            self::Obstructed => 'Obstrué',
            self::Destroyed => 'Détruit',
        };
    }
}
