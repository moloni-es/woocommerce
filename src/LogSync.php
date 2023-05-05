<?php

namespace MoloniES;

class LogSync
{
    /**
     * Validity of each log in seconds
     * @var int
     */
    private static $logValidity = 20;

    /**
     * Procedure to check if an entity has been synced recently
     *
     * @param $typeId int
     * @param $entityId int
     *
     * @return bool
     */
    public static function wasSyncedRecently(int $typeId, int $entityId): bool
    {
        self::deleteOldLogs(); //delete old logs before checking entry

        if (self::getOne($typeId, $entityId)) {
            return true; //if an entry was found
        }

        self::addLog($typeId, $entityId); //add new entry

        return false; //if an entry was NOT found
    }

    /**
     * Checks for a log entry
     *
     * @param int $typeId
     * @param int $entityId
     *
     * @return bool
     */
    public static function getOne(int $typeId, int $entityId): bool
    {
        global $wpdb;

        $query = 'SELECT COUNT(*) FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_sync_logs 
            where `type_id` = ' . $typeId . ' AND `entity_id` =' . $entityId;

        $queryResult = $wpdb->get_row($query, ARRAY_A);

        return !((int)$queryResult['COUNT(*)'] === 0);
    }

    /**
     * Gets all database entries
     */
    public static function getAll()
    {
        global $wpdb;

        $query = 'SELECT * FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_sync_logs';

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Adds a new log
     *
     * @param int $typeId
     * @param int $entityId
     */
    public static function addLog(int $typeId, int $entityId)
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
     * Deletes logs that have more than defined seconds (default 20)
     */
    public static function deleteOldLogs(): void
    {
        global $wpdb;

        $wpdb->query('DELETE FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_sync_logs WHERE sync_date < ' . time());
    }
}