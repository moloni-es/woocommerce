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
     * @param $typeId int
     * @param $entityId int
     * @return bool
     */
    public static function wasSyncedRecently($typeId, $entityId)
    {
        self::deleteOldLogs(); //delete old logs before checking entry

        if (self::getOne($typeId, $entityId)) {
            return true; //if an entry was found
        }

        self::addLog($typeId, $entityId); //add new entry

        return false; //if an entry was NOT found
    }

    /**
     * Checks for an log entry
     * @param $typeId
     * @param $entityId
     * @return bool
     */
    public static function getOne($typeId, $entityId)
    {
        global $wpdb;

        $query = 'SELECT COUNT(*) FROM moloni_sync_logs 
            where `type_id` = ' . $typeId . ' AND `entity_id` =' . $entityId;

        $queryResult = $wpdb->get_row($query, ARRAY_A);

        return !((int)$queryResult['COUNT(*)'] === 0);
    }

    /**
     * Gets all database entries
     * @return array|object|null
     */
    public static function getAll()
    {
        global $wpdb;

        $query = 'SELECT * FROM moloni_sync_logs';

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Adds a new log
     * @param $typeId int
     * @param $entityId int
     * @return bool
     */
    public static function addLog($typeId, $entityId)
    {
        global $wpdb;

        $wpdb->insert(
            'moloni_sync_logs',
            [
                'type_id' => $typeId,
                'entity_id' => $entityId,
                'sync_date' => time() + self::$logValidity,
            ]
        );

        return true;
    }

    /**
     * Deletes logs that have more than defined seconds (default 20)
     * @return bool
     */
    public static function deleteOldLogs()
    {
        global $wpdb;

        $wpdb->query('DELETE FROM moloni_sync_logs WHERE sync_date < ' . time());

        return true;
    }
}