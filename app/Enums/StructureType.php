<?php

namespace App\Enums;

enum StructureType: string
{
    case Caniveau = 'caniveau';
    case Collector = 'collector';
    case Dike = 'dike';
    case Manhole = 'manhole';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Caniveau => 'Caniveau',
            self::Collector => 'Collecteur',
            self::Dike => 'Digue',
            self::Manhole => 'Regard',
            self::Unknown => 'Non précisé',
        };
    }
}
