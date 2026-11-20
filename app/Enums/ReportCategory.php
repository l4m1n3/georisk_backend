<?php

namespace App\Enums;

enum ReportCategory: string
{
    case RiskZone = 'risk_zone';
    case DrainageStructure = 'drainage_structure';

    public function label(): string
    {
        return match ($this) {
            self::RiskZone => 'Zone à risque',
            self::DrainageStructure => 'Ouvrage de drainage',
        };
    }
}
