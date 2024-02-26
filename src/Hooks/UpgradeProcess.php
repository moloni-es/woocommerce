<?php

namespace MoloniES\Hooks;

use MoloniES\Activators\Updater;
use MoloniES\Plugin;

class UpgradeProcess {
    public $parent;

    /**
     * Constructor
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);
    }

    /**
     * Some action performed just after Moloni plugin is updated
     *
     * @param $upgrader_object
     * @param $options
     *
     * @return void
     */
    public function upgradeProcessComplete($upgrader_object, $options)
    {
        $ourAwesomePlugin = plugin_basename(MOLONI_ES_PLUGIN_FILE);

        if ($options['action'] === 'update' && $options['type'] === 'plugin' && isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === $ourAwesomePlugin) {
                    new Updater();

                    break;
                }
            }
        }
    }
}
