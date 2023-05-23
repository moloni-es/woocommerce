<?php

namespace MoloniES\Helpers;

use MoloniES\Enums\Boolean;

class ProductAssociations
{
    //          RETRIEVES          //

    public static function findByWcId($wcId)
    {
        $condition = 'wc_product_id = ' . (int)$wcId;

        return self::fetch($condition);
    }

    public static function findByWcParentId($wcParentId)
    {
        $condition = 'wc_parent_id = ' . (int)$wcParentId;

        return self::fetch($condition);
    }

    public static function findByMoloniId($mlId)
    {
        $condition = 'ml_product_id = ' . (int)$mlId;

        return self::fetch($condition);
    }

    public static function findByMoloniParentId($mlParentId)
    {
        $condition = 'ml_parent_id = ' . (int)$mlParentId;

        return self::fetch($condition);
    }

    //          CRUD          //

    public static function add($wcId = 0, $wcParentId = 0, $mlProductId = 0, $mlParentId = 0)
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->get_blog_prefix() . 'moloni_es_product_associations',
            [
                'wc_product_id' => (int)$wcId,
                'wc_parent_id' => (int)$wcParentId,
                'ml_product_id' => (int)$mlProductId,
                'ml_parent_id' => (int)$mlParentId,
                'active' => Boolean::YES,
            ]
        );
    }

    public static function deleteById($id): void
    {
        $condition = 'id = ' . (int)$id;

        self::delete($condition);
    }

    public static function deleteByWcId($wcId): void
    {
        $condition = 'wc_product_id = ' . (int)$wcId;

        self::delete($condition);
    }

    public static function deleteByWcParentId($wcParentId): void
    {
        $condition = 'wc_parent_id = ' . (int)$wcParentId;

        self::delete($condition);
    }

    public static function deleteByMoloniId($mlId): void
    {
        $condition = 'ml_product_id = ' . (int)$mlId;

        self::delete($condition);
    }

    public static function deleteByMoloniParentId($mlParentId): void
    {
        $condition = 'ml_parent_id = ' . (int)$mlParentId;

        self::delete($condition);
    }

    //          Privates          //

    private static function fetch($condition = '')
    {
        global $wpdb;

        $query = 'SELECT * FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_product_associations WHERE ';
        $query .= $condition;

        return $wpdb->get_row($query, ARRAY_A);
    }

    private static function delete($condition = '')
    {
        global $wpdb;

        $query = 'DELETE FROM ' . $wpdb->get_blog_prefix() . 'moloni_es_product_associations WHERE ';
        $query .= $condition;

        $wpdb->query($query);
    }
}