<?php

namespace MoloniES\Tools;

class SyncLogs
{
    /**
     * Validity of each log in seconds
     *
     * @var int
     */
    private static $logValidity = 20;

    //          Publics          //

    /**
     * Adds a new log
     *
     * @param int $typeId
     * @param int $entityId
     *
     * @return void
     */
    public static function addTimeout(int $typeId, int $entityId)
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->get_blog_prefix() . 'moloni_es_sync_logs',
            [
                'type_id' => $typeId,
                'entity_id' => $entityId,
                'sync_date' => time() + self::$logValidity,
            ]
        );
    }

    /**
     * Procedure to check if an entity has been synced recently
     *
     * @param int|int[] $typeId
     * @param int $entityId
     *
     * @return bool
     */
    public static function hasTimeout($typeId, int $entityId): bool
    {
        /** Delete old logs before checking entry */
        self::removeExpiredTimeouts();

        return self::checkIfExists($typeId, $entityId);
    }

    /**
     * Remove expired timeouts
     *
     * @return void
     */
    public static function removeTimeouts(): void
    {
        self::removeExpiredTimeouts();
    }

    //          Privates          //

    /**
     * Checks for a log entry
     *
     * @param int|int[] $typeId
     * @param int $entityId
     *
     * @return bool
     */
    private static function checkIfExists($typeId, int $entityId): bool
    {
        global $wpdb;

        $query = 'SELECT COUNT(*) FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_sync_logs 
            WHERE `entity_id` = ' . $entityId . ' AND';

        if (is_array($typeId)) {
            $query .= ' `type_id` IN (';

            foreach ($typeId as $value) {
                $query .= (int)$value . ',';
            }

            $query = rtrim($query, ',');
            $query .= ')';
        } else {
            $query .= ' `type_id` = ' . (int)$typeId;
        }

        $queryResult = $wpdb->get_row($query, ARRAY_A);

        return (int)$queryResult['COUNT(*)'] > 0;
    }

    /**
     * Deletes logs that have more than defined seconds (default 20)
     *
     * @return void
     */
    private static function removeExpiredTimeouts(): void
    {
        global $wpdb;

        $wpdb->query('DELETE FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_sync_logs WHERE sync_date < ' . time());
    }
}
