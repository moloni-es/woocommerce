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
            <strong class="name">
                <?= __('Import stock from Moloni', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('This tool will use the Moloni products to update product stocks in your WooCommerce store.', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <button id="importStocksButton" class="button button-large">
                <?= __('Import stock', 'moloni_es') ?>
            </button>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name">
                <?= __('Import products from Moloni', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('Use this tool to create all Moloni products in your WooCommerce store.', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <button id="importProductsButton" class="button button-large">
                <?= __('Import products', 'moloni_es') ?>
            </button>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name">
                <?= __('Export stock to Moloni', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('This tool will use the WooCommerce products to update product stocks in your Moloni account.', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <button id="exportStocksButton" class="button button-large">
                <?= __('Export stock', 'moloni_es') ?>
            </button>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name">
                <?= __('Export products to Moloni', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('Use this tool to create all WooCommerce products in your Moloni account.', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <button id="exportProductsButton" class="button button-large">
                <?= __('Export products', 'moloni_es') ?>
            </button>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name">
                <?= __('Reinstall Moloni Webhooks', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('Remove this store Webhooks and install them again', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=reinstallWebhooks')) ?>'>
                <?= __('Reinstall Moloni Webhooks', 'moloni_es') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th style="padding: 2rem">
            <strong class="name">
                <?= __('Logout', 'moloni_es') ?>
            </strong>
            <p class='description'>
                <?= __('We will keep the data regarding the documents already issued', 'moloni_es') ?>
            </p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large button-primary"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=logout')) ?>'>
                <?= __('Logout', 'moloni_es') ?>
            </a>
        </td>
    </tr>
    </tbody>
</table>

<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Tools/ActionModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Tools/ExportProductsModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Tools/ExportStocksModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Tools/ImportProductsModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Tools/ImportStocksModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.Tools.init();
    });
</script>
