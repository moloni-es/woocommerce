<?php

namespace MoloniES\Traits;

use MoloniES\Enums\Boolean;

trait SyncFieldsSettingsTrait
{
    protected function productShouldSyncEAN(): bool
    {
        return defined('SYNC_FIELDS_EAN') && (int)SYNC_FIELDS_EAN === Boolean::YES;
    }

    protected function productShouldSyncCategories(): bool
    {
        return defined('SYNC_FIELDS_CATEGORIES') && (int)SYNC_FIELDS_CATEGORIES === Boolean::YES;
    }

    protected function productShouldSyncStock(): bool
    {
        return defined('SYNC_FIELDS_STOCK') && (int)SYNC_FIELDS_STOCK === Boolean::YES;
    }

    protected function productShouldSyncVisibility(): bool
    {
        return defined('SYNC_FIELDS_VISIBILITY') && (int)SYNC_FIELDS_VISIBILITY === Boolean::YES;
    }

    protected function productShouldSyncImage(): bool
    {
        return defined('SYNC_FIELDS_IMAGE') && (int)SYNC_FIELDS_IMAGE === Boolean::YES;
    }

    protected function productShouldSyncPrice(): bool
    {
        return defined('SYNC_FIELDS_PRICE') && (int)SYNC_FIELDS_PRICE === Boolean::YES;
    }

    protected function productShouldSyncDescription(): bool
    {
        return defined('SYNC_FIELDS_DESCRIPTION') && (int)SYNC_FIELDS_DESCRIPTION === Boolean::YES;
    }

    protected function productShouldSyncName(): bool
    {
        return defined('SYNC_FIELDS_NAME') && (int)SYNC_FIELDS_NAME === Boolean::YES;
    }
}