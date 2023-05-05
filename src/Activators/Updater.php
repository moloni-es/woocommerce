<?php

namespace MoloniES\Activators;

use WP_Site;

class Updater
{
    public function __construct()
    {
        $this->updateTableNames();
    }

    //          Privates          //

    /**
     * Check if we need to upgrade the table name to add the wp_ prefix
     *
     * @return void
     */
    private function updateTableNames(): void
    {
        global $wpdb;

        $query = $wpdb->prepare('SHOW TABLES LIKE %s', 'moloni_es_api');

        // If we still have the old table names, lets update them
        if ($wpdb->get_var($query) === 'moloni_es_api') {
            if (is_multisite() && function_exists('get_sites')) {
                /** @var WP_Site[] $sites */
                $sites = get_sites();

                foreach ($sites as $site) {
                    $prefix = $wpdb->get_blog_prefix($site->id);

                    $this
                        ->runModification('moloni_es_api', $prefix . 'moloni_es_api')
                        ->runModification('moloni_es_api_config', $prefix . 'moloni_es_api_config')
                        ->runModification('moloni_sync_logs', $prefix . 'moloni_es_sync_logs');
                }
            } else {
                $prefix = $wpdb->get_blog_prefix();

                $this
                    ->runModification('moloni_es_api', $prefix . 'moloni_es_api')
                    ->runModification('moloni_es_api_config', $prefix . 'moloni_es_api_config')
                    ->runModification('moloni_sync_logs', $prefix . 'moloni_es_sync_logs');
            }
        }
    }

    //          Auxiliary          //

    /**
     * Alters old table name
     *
     * @param string $oldName Old table name
     * @param string $newName New table name
     *
     * @return Updater
     */
    private function runModification(string $oldName, string $newName): Updater
    {
        global $wpdb;

        $wpdb->query(sprintf('RENAME TABLE %s TO %s ;', $oldName, $newName));

        return $this;
    }
}