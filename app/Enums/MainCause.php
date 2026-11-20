<?php

namespace App\Enums;

enum MainCause: string
{
    case Obstruction = 'obstruction';
    case NoDrain = 'no_drain';
    case DikeOverflow = 'dike_overflow';

    public function label(): string
    {
        return match ($this) {
            self::Obstruction => 'Obstruction',
            self::NoDrain => 'Absence de drainage',
            self::DikeOverflow => 'Débordement de digue',
        };
    }
}
