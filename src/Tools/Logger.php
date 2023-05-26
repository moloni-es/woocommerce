<?php

namespace MoloniES\Tools;

use MoloniES\Storage;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "INSERT INTO `" . $wpdb->get_blog_prefix() . "moloni_es_logs`(log_level, company_id, message, context, created_at) VALUES(%s, %d, %s, %s, %s)",
            $level,
            Storage::$MOLONI_ES_COMPANY_ID ?? 0,
            $message,
            json_encode($context),
            date('Y-m-d H:i:s')
        );

        $wpdb->query($query);
    }
}