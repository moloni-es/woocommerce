<?php

namespace MoloniES\Services\WcProduct\Abstracts;

use MoloniES\Traits\SyncFieldsSettingsTrait;
use MoloniES\Services\WcProduct\Interfaces\WcSyncInterface;

abstract class WcProductSyncAbstract implements WcSyncInterface
{
    use SyncFieldsSettingsTrait;

    protected $results = [];

    //            Protecteds            //

    //            Abstracts            //

    protected abstract function createAssociation();
}