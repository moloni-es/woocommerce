<?php

namespace MoloniES\Services\WcProduct\Abstracts;

use MoloniES\Services\WcProduct\Interfaces\WcSyncInterface;

abstract class WcStockSyncAbstract implements WcSyncInterface
{
    protected $results = [];
}