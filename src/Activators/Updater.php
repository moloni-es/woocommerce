<?php

namespace MoloniES\Activators;

use WP_Site;

class Updater
{
    public function __construct()
    {
        $this->updateTableNames();
        $this->createTablesIfMissing();
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

    /**
     * Check if we need to create the new table (new from 2.0.0)
     *
     * @return void
     */
    private function createTablesIfMissing(): void
    {
        global $wpdb;

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                $this->runCreateLog($wpdb->get_blog_prefix($site->id));
                $this->runCreateProductAssociations($wpdb->get_blog_prefix($site->id));
            }
        } else {
            $this->runCreateLog($wpdb->get_blog_prefix());
            $this->runCreateProductAssociations($wpdb->get_blog_prefix());
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

    /**
     * Create log table, if missing
     *
     * @param string $prefix Database prefix
     *
     * @return void
     */
    private function runCreateLog(string $prefix): void
    {
        global $wpdb;

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_es_logs` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                log_level VARCHAR(100) NULL,
                company_id INT,
                message TEXT,
                context TEXT,
                created_at TIMESTAMP default CURRENT_TIMESTAMP
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }

    /**
     * Create log table, if missing
     *
     * @param string $prefix Database prefix
     *
     * @return void
     */
    private function runCreateProductAssociations(string $prefix): void
    {
        global $wpdb;

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_es_product_associations` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                wc_product_id INT(11) NOT NULL,
                wc_parent_id INT(11) DEFAULT 0,
                ml_product_id INT(11) NOT NULL,
                ml_parent_id INT(11) DEFAULT 0,
                active INT(11) DEFAULT 1
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }
}