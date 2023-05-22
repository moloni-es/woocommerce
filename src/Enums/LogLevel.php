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
                'label' => __('Error', 'moloni_es'),
                'value' => self::ERROR
            ],
            [
                'label' => __('Warning', 'moloni_es'),
                'value' => self::WARNING
            ],
            [
                'label' => __('Informative', 'moloni_es'),
                'value' => self::INFO
            ],
            [
                'label' => __('Alert', 'moloni_es'),
                'value' => self::ALERT
            ],
            [
                'label' => __('Critical', 'moloni_es'),
                'value' => self::CRITICAL
            ],
            [
                'label' => __('Emergency', 'moloni_es'),
                'value' => self::EMERGENCY
            ],
            [
                'label' => __('Observation', 'moloni_es'),
                'value' => self::NOTICE
            ]
        ];
    }

    public static function getTranslation(string $type): ?string
    {
        switch ($type) {
            case self::ERROR:
                return __('Error', 'moloni_es');
            case self::WARNING:
                return __('Warning', 'moloni_es');
            case self::INFO:
                return __('Informative', 'moloni_es');
            case self::DEBUG:
                return __('Debug', 'moloni_es');
            case self::ALERT:
                return __('Alert', 'moloni_es');
            case self::CRITICAL:
                return __('Critical', 'moloni_es');
            case self::EMERGENCY:
                return __('Emergency', 'moloni_es');
            case self::NOTICE:
                return __('Observation', 'moloni_es');
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