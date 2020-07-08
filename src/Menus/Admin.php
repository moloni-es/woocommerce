<?php

namespace MoloniES\Menus;

use MoloniES\Plugin;

class Admin
{

    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('admin_menu', [$this, 'admin_menu'], 55.6);
        add_action('admin_notices', '\MoloniES\Notice::showMessages');
    }

    public function admin_menu()
    {
        if (current_user_can('manage_woocommerce')) {
            $logoDir = MOLONI_ES_IMAGES_URL . 'small_logo.png';
            add_menu_page(__('Moloni', 'moloni_es'), __('Moloni', 'moloni_es'), 'manage_woocommerce', 'molonies', [$this->parent, 'run'], $logoDir, 55.5);
        }
    }
}
