<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<br>
<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Import stock from Moloni' , 'moloni_es') ?></strong>
            <p class='description'><?= __('Match WooCommerce stock with Moloni values' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large">
                <?= __('Import stock' , 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Import products from Moloni' , 'moloni_es') ?></strong>
            <p class='description'><?= __('Import products from Moloni account to WooCommerce' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large">
                <?= __('Import products' , 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Export stock to Moloni' , 'moloni_es') ?></strong>
            <p class='description'><?= __('Match Moloni stock with WooCommerce values' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large">
                <?= __('Export stock' , 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Export products to Moloni' , 'moloni_es') ?></strong>
            <p class='description'><?= __('Export products from WooCommerce to Moloni account' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large">
                <?= __('Export products' , 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Remove pending orders' , 'moloni_es') ?></strong>
            <p class='description'><?= __('Remove all orders from the order list' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=remInvoiceAll')) ?>'>
                <?= __('Remove pending orders' , 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Reinstall Moloni Webhooks' , 'moloni_es') ?></strong>
            <p class='description'><?= __('Remove this store Webhooks and install them again' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=reinstallWebhooks')) ?>'>
                <?= __('Reinstall Moloni Webhooks' , 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Logout' , 'moloni_es') ?></strong>
            <p class='description'><?= __('We will keep the data regarding the documents already issued' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large button-primary"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=logout')) ?>'>
                <?= __('Logout' , 'moloni_es') ?>
            </a>
        </td>
    </tr>
    </tbody>
</table>

<script>
    Moloni.Tools.init();
</script>