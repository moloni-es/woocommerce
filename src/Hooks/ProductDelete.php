<?php

namespace MoloniES\Hooks;

use MoloniES\Plugin;
use MoloniES\Tools\ProductAssociations;

class ProductDelete
{
    /**
     * Main class
     *
     * @var Plugin
     */
    public $parent;

    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;

        add_action('delete_post', [$this, 'deletePost'], 99);
    }

    public function deletePost($post_id)
    {
        if (get_post_type($post_id) != 'product') {
            return;
        }

        ProductAssociations::deleteByWcId($post_id);
        ProductAssociations::deleteByWcParentId($post_id);
    }
}
