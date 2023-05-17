<?php

namespace MoloniES\Enums;

class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    public static function getForRender(): array
    {
        return [
            [
                'label' => __('Error'),
                'value' => self::ERROR
            ],
            [
                'label' => __('Warning'),
                'value' => self::WARNING
            ],
            [
                'label' => __('Informative'),
                'value' => self::INFO
            ],
            [
                'label' => __('Alert'),
                'value' => self::ALERT
            ],
            [
                'label' => __('Critical'),
                'value' => self::CRITICAL
            ],
            [
                'label' => __('Emergency'),
                'value' => self::EMERGENCY
            ],
            [
                'label' => __('Observation'),
                'value' => self::NOTICE
            ]
        ];
    }

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

    public static function getClass(string $type): ?string
    {
        switch ($type) {
            case self::CRITICAL:
            case self::EMERGENCY:
            case self::ERROR:
                return 'chip--red';
            case self::ALERT:
            case self::WARNING:
                return 'chip--yellow';
            case self::NOTICE:
            case self::INFO:
                return 'chip--blue';
            case self::DEBUG:
                return 'chip--neutral';
        }

        return $type;
    }
}