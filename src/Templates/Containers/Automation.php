<?php

if (!defined('ABSPATH')) {
    exit;
}

use MoloniES\API\Warehouses;
use MoloniES\Exceptions\Error;
use MoloniES\Enums\Boolean;
use MoloniES\Enums\AutomaticDocumentsStatus;
use MoloniES\Services\MoloniProduct\Helpers\Variants\FindOrCreatePropertyGroup;

try {
    $warehouses = Warehouses::queryWarehouses();
} catch (Error $e) {
    $e->showError();
    return;
}
/*
$wcProduct = new WC_Product_Variable(254);

echo json_encode((new FindOrCreatePropertyGroup($wcProduct))->handle());

die;*/
?>

<form method='POST' action='<?= admin_url('admin.php?page=molonies&tab=automation') ?>' id='formOpcoes'>
    <input type='hidden' value='saveAutomations' name='action'>
    <div>
        <h2 class="title">
            <?= __('Automatic actions from Wordpress', 'moloni_es') ?>
        </h2>

        <div class="subtitle">
            (<?= __('This actions happen when an action occours in your Wordpress site.', 'moloni_es') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="invoice_auto"><?= __('Create document automatically', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="invoice_auto" name='opt[invoice_auto]' class='inputOut'>
                        <?php $invoiceAuto = defined('INVOICE_AUTO') ? (int)INVOICE_AUTO : Boolean::NO; ?>

                        <option value='0' <?= ($invoiceAuto === Boolean::NO ? 'selected' : '') ?>>
                            <?= __('No', 'moloni_es') ?>
                        </option>
                        <option value='1' <?= ($invoiceAuto === Boolean::YES ? 'selected' : '') ?>>
                            <?= __('Yes', 'moloni_es') ?>
                        </option>
                    </select>
                    <p class='description'><?= __('Automatically create document when an order is paid', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr id="invoice_auto_status_line" <?= ($invoiceAuto === Boolean::NO ? 'style="display: none;"' : '') ?>>
                <th>
                    <label for="invoice_auto_status"><?= __('Create documents when the order is', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="invoice_auto_status" name='opt[invoice_auto_status]' class='inputOut'>
                        <?php $invoiceAutoStatus = defined('INVOICE_AUTO_STATUS') ? INVOICE_AUTO_STATUS : ''; ?>

                        <option value='completed' <?= ($invoiceAutoStatus === AutomaticDocumentsStatus::COMPLETED ? 'selected' : '') ?>>
                            <?= __('Complete', 'moloni_es') ?>
                        </option>
                        <option value='processing' <?= ($invoiceAutoStatus === AutomaticDocumentsStatus::PROCESSING ? 'selected' : '') ?>>
                            <?= __('Processing', 'moloni_es') ?>
                        </option>
                    </select>
                    <p class='description'><?= __('Documents will be created automatically once they are in the selected state', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync"><?= __('Sync products', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync" name='opt[moloni_product_sync]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When saving a product in WooCommerce, the plugin will automatically create the product in Moloni or update if it already exists (only if product has SKU set)', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync"><?= __('Sync stocks automatically', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync" name='opt[moloni_stock_sync]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_STOCK_SYNC') && MOLONI_STOCK_SYNC === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (defined('MOLONI_STOCK_SYNC') && MOLONI_STOCK_SYNC === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('Automatic stock synchronization', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync_warehouse"><?= __('Sync stocks warehouse', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync_warehouse" name='opt[moloni_stock_sync_warehouse]' class='inputOut'>
                        <option value='0'>
                            <?= __('Default warehouse', 'moloni_es') ?>
                        </option>

                        <?php $hookStockSyncWarehouse = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0; ?>

                        <optgroup label="<?= __('Warehouses', 'moloni_es') ?>">
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option
                                        value='<?= $warehouse['warehouseId'] ?>' <?= ($hookStockSyncWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                    <?= $warehouse['name'] ?> (<?= $warehouse['number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <p class='description'>
                        <?= __('This warehouse will be used when a product is inserted or updated in Wordpress', 'moloni_es') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title">
            <?= __('Automatic actions from Moloni', 'moloni_es') ?>
        </h2>

        <div class="subtitle">
            (<?= __('This actions happen when an action occours in your Moloni account.', 'moloni_es') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="hook_product_sync"><?= __('Sync products', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="hook_product_sync" name='opt[hook_product_sync]' class='inputOut'>
                        <option value='0' <?= (defined('HOOK_PRODUCT_SYNC') && HOOK_PRODUCT_SYNC === '0' ? 'selected' : '') ?>><?= __('No', 'moloni_es') ?></option>
                        <option value='1' <?= (defined('HOOK_PRODUCT_SYNC') && HOOK_PRODUCT_SYNC === '1' ? 'selected' : '') ?>><?= __('Yes', 'moloni_es') ?></option>
                    </select>
                    <p class='description'><?= __('When saving a product in Moloni, the plugin will automatically create the product in WooCommerce or update if it already exists', 'moloni_es') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="hook_stock_sync"><?= __('Sync stocks automatically', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="hook_stock_sync" name='opt[hook_stock_sync]' class='inputOut'>
                        <option value='0' <?= (defined('HOOK_STOCK_SYNC') && HOOK_STOCK_SYNC === '0' ? 'selected' : '') ?>>
                            <?= __('No', 'moloni_es') ?>
                        </option>
                        <option value='1' <?= (defined('HOOK_STOCK_SYNC') && HOOK_STOCK_SYNC === '1' ? 'selected' : '') ?>>
                            <?= __('Yes', 'moloni_es') ?>
                        </option>
                    </select>
                    <p class='description'>
                        <?= __('When a stock movement is created in moloni, the movement will be recreated in WooCommerce (if product exists)', 'moloni_es') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="hook_stock_sync_warehouse"><?= __('Sync stocks warehouse', 'moloni_es') ?></label>
                </th>
                <td>
                    <select id="hook_stock_sync_warehouse" name='opt[hook_stock_sync_warehouse]' class='inputOut'>
                        <option value='1'>
                            <?= __('Accumulated stock', 'moloni_es') ?>
                        </option>

                        <?php $hookStockSyncWarehouse = defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1 ?>

                        <optgroup label="<?= __('Warehouses', 'moloni_es') ?>">
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option
                                        value='<?= $warehouse['warehouseId'] ?>' <?= ($hookStockSyncWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                    <?= $warehouse['name'] ?> (<?= $warehouse['number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>

                    <p class='description'>
                        <?= __('This warehouse will be used when a product is inserted or updated in Moloni', 'moloni_es') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title">
            <?= __('Synchronization extras', 'moloni_es') ?>
        </h2>

        <div class="subtitle">
            (<?= __('This settings will be applied to all automatic actions.', 'moloni_es') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label><?= __('Fields to sync', 'moloni_es') ?></label>
                </th>
                <td>
                    <fieldset>
                        <input type="checkbox" name="opt[sync_fields_name]" id="name"
                               value="1" <?= (defined('SYNC_FIELDS_NAME') && SYNC_FIELDS_NAME === '1' ? 'checked' : '') ?>/><label
                                for="name"><?= __('Name', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_price]" id="price"
                               value="1" <?= (defined('SYNC_FIELDS_PRICE') && SYNC_FIELDS_PRICE === '1' ? 'checked' : '') ?>/><label
                                for="price"><?= __('Price', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_description]]" id="description"
                               value="1" <?= (defined('SYNC_FIELDS_DESCRIPTION') && SYNC_FIELDS_DESCRIPTION === '1' ? 'checked' : '') ?>/><label
                                for="description"><?= __('Description', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_visibility]" id="visibility"
                               value="1" <?= (defined('SYNC_FIELDS_VISIBILITY') && SYNC_FIELDS_VISIBILITY === '1' ? 'checked' : '') ?>/><label
                                for="visibility"><?= __('Visibility', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_stock]" id="stock"
                               value="1" <?= (defined('SYNC_FIELDS_STOCK') && SYNC_FIELDS_STOCK === '1' ? 'checked' : '') ?>/><label
                                for="stock"><?= __('Stock', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_categories]" id="categories"
                               value="1" <?= (defined('SYNC_FIELDS_CATEGORIES') && SYNC_FIELDS_CATEGORIES === '1' ? 'checked' : '') ?>/><label
                                for="categories"><?= __('Categories', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_ean]" id="ean"
                               value="1" <?= (defined('SYNC_FIELDS_EAN') && SYNC_FIELDS_EAN === '1' ? 'checked' : '') ?>/><label
                                for="ean"><?= __('EAN', 'moloni_es') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_image]" id="image"
                               value="1" <?= (defined('SYNC_FIELDS_IMAGE') && SYNC_FIELDS_IMAGE === '1' ? 'checked' : '') ?>/><label
                                for="image"><?= __('Image', 'moloni_es') ?></label><br/>
                    </fieldset>
                    <p class='description'>
                        <?= __('Optional field that will sync when synchronizing products', 'moloni_es') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?= __('Save changes', 'moloni_es') ?>">
                </td>
            </tr>

            </tbody>
        </table>
    </div>
</form>

<script>
    Moloni.Automations.init();
</script>