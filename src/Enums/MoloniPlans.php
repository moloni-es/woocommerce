<?php

namespace MoloniES\Enums;

class MoloniPlans
{
    public const EXPERIMENTAL = 1;
    public const PRO = 2;
    public const FLEX = 3;
    public const BASE  = 4;

    public const PLANS_WITH_VARIANTS = [
        self::EXPERIMENTAL,
        self::PRO,
    ];

    public static function hasVariants(?int $planId = 0): bool
    {
        return in_array($planId, self::PLANS_WITH_VARIANTS, true);
    }
}
