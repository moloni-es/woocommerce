<?php

namespace MoloniES\Enums;

class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    public static function getTranslation(string $type): ?string
    {
        switch ($type) {
            case self::ERROR:
                return __('Error');
            case self::WARNING:
                return __('Warning');
            case self::INFO:
                return __('Informative');
            case self::DEBUG:
                return __('Debug');
            case self::ALERT:
                return __('Alert');
            case self::CRITICAL:
                return __('Critical');
            case self::EMERGENCY:
                return __('Emergency');
            case self::NOTICE:
                return __('Observation');
        }

        return $type;
    }
}