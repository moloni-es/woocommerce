<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<br>
<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('Reinstall Moloni Webhooks', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('Remove this store Webhooks and install them again', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a class="button button-large"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=reinstallWebhooks')) ?>'>
                <?= __('Reinstall Moloni Webhooks', 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('List Moloni products', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('List all products in Moloni company and import data into your WooCommerce store', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= admin_url('admin.php?page=molonies&tab=moloniProductsList') ?>'
               class="button button-large"
            >
                <?= __('View Moloni products', 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('List WooCommerce Products', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('List all products in WooCommerce store and export data to your Moloni company', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= admin_url('admin.php?page=molonies&tab=wcProductsList') ?>'
               class="button button-large"
            >
                <?= __('View WooCommerce Products', 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('Logout', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('We will keep the data regarding the documents already issued', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a class="button button-large button-primary"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=logout')) ?>'>
                <?= __('Logout', 'moloni_es') ?>
            </a>
        </td>
    </tr>
    </tbody>
</table>

<script>
    jQuery(document).ready(function () {
        Moloni.Tools.init();
    });
</script>
