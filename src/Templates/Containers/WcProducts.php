<?php

use MoloniES\API\Warehouses;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Exceptions\HelperException;
use MoloniES\Services\WcProduct\Page\FetchAndCheckProducts;

if (!defined('ABSPATH')) {
    exit;
}

$page = (int)($_REQUEST['paged'] ?? 1);
$filters = [
    'filter_name' => sanitize_text_field($_REQUEST['filter_name'] ?? ''),
    'filter_reference' => sanitize_text_field($_REQUEST['filter_reference'] ?? ''),
];

$service = new FetchAndCheckProducts();
$service->setPage($page);
$service->setFilters($filters);

try {
    $service->run();
} catch (HelperException|APIExeption $e) {
    $e->showError();
    return;
}

$rows = $service->getRows();
$paginator = $service->getPaginator();

$currentAction = admin_url('admin.php?page=molonies&tab=wcProductsList');
$backAction = admin_url('admin.php?page=molonies&tab=tools');
?>

<h3>
    <?= __('WooCommerce product listing', 'moloni_es') ?>
</h3>

<h4>
    <?= __('This list will display all WooCommerce products from the current store and indicate errors/alerts that may exist.', 'moloni_es') ?>
    <?= __('All actions on this page will be in the WooCommerce -> Moloni direction.', 'moloni_es') ?>
</h4>

<div class="notice notice-success m-0">
    <p>
        <?= __('Do you want to export your entire catalogue?', 'moloni_es') ?>
    </p>

    <p class="">
        <button id="exportProductsButton" class="button button-large">
            <?= __('Export all products', 'moloni_es') ?>
        </button>

        <button id="exportStocksButton" class="button button-large">
            <?= __('Export all stock', 'moloni_es') ?>
        </button>
    </p>
</div>

<div class="notice notice-warning m-0 mt-4">
    <p>
        <?= __('Moloni stock values based on:', 'moloni_es') ?>
    </p>
    <p>
        <?php
        try {
            $warehouse = Warehouses::queryWarehouse([
                'warehouseId' => $service->getWarehouseId()
            ])['data']['warehouse']['data'];
        } catch (APIExeption $e) {
            $e->showError();
            return;
        }

        echo '- ' . __('Warehouse', 'moloni_es');
        echo '<b>';
        echo ': ' . $warehouse['name'] . ' (' . $warehouse['number'] . ')';
        echo '</b>';
        ?>
    </p>
</div>

<form method="get" action='<?= $currentAction ?>' class="list_form">
    <input type="hidden" name="page" value="molonies">
    <input type="hidden" name="paged" value="<?= $page ?>">
    <input type="hidden" name="tab" value="wcProductsList">

    <div class="tablenav top">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni_es') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?= __('Run exports', 'moloni_es') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>

    <table class="wp-list-table widefat striped posts">
        <thead>
        <tr>
            <th>
                <a><?= __('Name', 'moloni_es') ?></a>
            </th>
            <th>
                <a><?= __('Reference', 'moloni_es') ?></a>
            </th>
            <th>
                <a><?= __('Type', 'moloni_es') ?></a>
            </th>
            <th>
                <a><?= __('Alerts', 'moloni_es') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?= __('Export product', 'moloni_es') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Export stock', 'moloni_es') ?></a>
            </th>
        </tr>
        <tr>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_name"
                        value="<?= $filters['filter_name'] ?>"
                >
            </th>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_reference"
                        value="<?= $filters['filter_reference'] ?>"
            </th>
            <th></th>
            <th></th>
            <th class="flex flex-row gap-2">
                <button type="submit" class="button button-primary">
                    <?= __('Search', 'moloni_es') ?>
                </button>

                <a href='<?= $currentAction ?>' class="button">
                    <?= __('Clear', 'moloni_es') ?>
                </a>
            </th>
            <th>
                <div class="text-center">
                    <input type="checkbox" class="checkbox_create_product_master m-0-important">
                </div>
            </th>
            <th>
                <div class="text-center">
                    <input type="checkbox" class="checkbox_update_stock_product_master m-0-important">
                </div>
            </th>
        </tr>
        </thead>

        <tbody>
        <?php if (!empty($rows) && is_array($rows)) : ?>
            <?php foreach ($rows as $row) : ?>
                <?= $row ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr class="text-center">
                <td colspan="100%">
                    <?= __('No WooCommerce products were found!', 'moloni_es') ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>

        <tfoot>
        <tr>
            <th>
                <a><?= __('Name', 'moloni_es') ?></a>
            </th>
            <th>
                <a><?= __('Reference', 'moloni_es') ?></a>
            </th>
            <th>
                <a><?= __('Type', 'moloni_es') ?></a>
            </th>
            <th>
                <a><?= __('Alerts', 'moloni_es') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?= __('Export product', 'moloni_es') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Export stock', 'moloni_es') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni_es') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?= __('Run exports', 'moloni_es') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>
</form>

<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Products/ActionModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Products/ExportProductsModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Products/ExportStocksModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.WcProducts.init({
            'create_action': "<?= __('product creation processes.', 'moloni_es') ?>",
            'update_action': "<?= __('product update processes.', 'moloni_es') ?>",
            'stock_action': "<?= __('stock update processes.', 'moloni_es') ?>",
            'processing_product': "<?= __('Processing product', 'moloni_es') ?>",
            'successfully_processed': "<?= __('Successfully processed', 'moloni_es') ?>",
            'error_in_the_process': "<?= __('Error in the process', 'moloni_es') ?>",
            'click_to_see': "<?= __('Click to see', 'moloni_es') ?>",
            'completed': "<?= __('Completed', 'moloni_es') ?>",
        });
    });
</script>
