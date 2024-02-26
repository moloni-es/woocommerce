<?php

namespace MoloniES\Traits;

use MoloniES\Enums\Boolean;

trait SettingsTrait
{
    protected function isSyncProductWithVariantsActive(): bool
    {
        return defined('SYNC_PRODUCTS_WITH_VARIANTS') && (int)SYNC_PRODUCTS_WITH_VARIANTS === Boolean::YES;
    }
}
