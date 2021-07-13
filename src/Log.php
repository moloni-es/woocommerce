<?php

namespace MoloniES;

use RuntimeException;

class Log
{

    private static $fileName = false;

    public static function write($message)
    {
        try {
            if (!is_dir(MOLONI_ES_DIR . '/logs') && !mkdir($concurrentDirectory = MOLONI_ES_DIR . '/logs') && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }

            $fileName = (defined('MOLONIES_COMPANY_ID') ? MOLONIES_COMPANY_ID : '000')
                . (self::$fileName ?: date('Ymd'))
                . '.log';

            $logFile = fopen(MOLONI_ES_DIR . '/logs/' . $fileName, 'ab');
            fwrite($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
        } catch (RuntimeException $exception) {

        }
    }

    public static function getFileUrl()
    {
        $fileName = (defined('MOLONIES_COMPANY_ID') ? MOLONIES_COMPANY_ID : '000')
            . (self::$fileName ? self::$fileName . '.log' : date('Ymd'))
            . '.log';

        return MOLONI_ES_PLUGIN_URL . '/logs/' . $fileName;
    }

    public static function removeLogs()
    {
        $logFiles = glob(MOLONI_ES_DIR . '/logs/*.log');
        if (!empty($logFiles) && is_array($logFiles)) {
            $deleteSince = strtotime(date('Y-m-d'));
            foreach ($logFiles as $file) {
                if (filemtime($file) < $deleteSince) {
                    unlink($file);
                }
            }
        }

    }

    public static function setFileName($name)
    {
        if (!empty($name)) {
            self::$fileName = $name;
        }
    }

}
