<?php

namespace MoloniES\Services\WcProduct\Interfaces;

interface WcSyncInterface
{
    public function run();

    public function saveLog();
}