<?php

use MoloniES\API\Warehouses;
use MoloniES\Exceptions\APIExeption;
use MoloniES\Services\MoloniProduct\Page\FetchAndCheckProducts;

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
} catch (APIExeption $e) {
    $e->showError();
    return;
}

$rows = $service->getRows();
$paginator = $service->getPaginator();

$currentAction = admin_url('admin.php?page=molonies&tab=moloniProductsList');
$backAction = admin_url('admin.php?page=molonies&tab=tools');
?>

<h3>
    <?= __('Moloni product list', 'moloni_es') ?>
</h3>

<h4>
    <?= __('This list will present all Moloni products from the current company and indicate errors/alerts that may exist.', 'moloni_es') ?>
    <?= __('All actions on this page will be in the Moloni -> WooCommerce direction.', 'moloni_es') ?>
</h4>

<div class="notice notice-success m-0">
    <p>
        <?= __('Do you want to import your entire catalogue?', 'moloni_es') ?>
    </p>

    <p class="">
        <button id="importProductsButton" class="button button-large">
            <?= __('Import all products', 'moloni_es') ?>
        </button>

        <button id="importStocksButton" class="button button-large">
            <?= __('Import all stock', 'moloni_es') ?>
        </button>
    </p>
</div>

<div class="notice notice-warning m-0 mt-4">
    <p>
        <?= __('Moloni stock values based on:', 'moloni_es') ?>
    </p>
    <p>
        <?php
        $warehouseId = $service->getWarehouseId();

        if ($warehouseId === 1) {
            echo '- <b>' . __('Accumulated stock from all warehouses.', 'moloni_es') . '</b>';
        } else {
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
        }
        ?>
    </p>
</div>

<form method="get" action='<?= $currentAction ?>'>
    <input type="hidden" name="page" value="molonies">
    <input type="hidden" name="paged" value="<?= $page ?>">
    <input type="hidden" name="tab" value="moloniProductsList">

    <div class="tablenav top">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni_es') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-imports" disabled>
            <?= __('Run imports', 'moloni_es') ?>
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
                <a><?= __('Import produt', 'moloni_es') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Import stock', 'moloni_es') ?></a>
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
            <th>
                <button type="submit" class="button button-primary">
                    <?= __('Search', 'moloni_es') ?>
                </button>

                <a href='<?= $currentAction ?>' class="button">
                    <?= __('Clear', 'moloni_es') ?>
                </a>
            </th>
            <th></th>
            <th></th>
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
                    <?= __('No Moloni products were found!', 'moloni_es') ?>
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
                <a><?= __('Import product', 'moloni_es') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Import stock', 'moloni_es') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni_es') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-imports" disabled>
            <?= __('Run imports', 'moloni_es') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>
</form>

<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Products/ActionModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Products/ImportProductsModal.php'; ?>
<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/Products/ImportStocksModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.MoloniProducts.init({
            'create_action': "<?= __('product creation processes.', 'moloni_es') ?>",
            'update_action': "<?= __('product update processes.', 'moloni_es') ?>",
            'stock_action': "<?= __('stock update processes.', 'moloni_es') ?>",
            'processing_product': "<?= __('Processing product', 'moloni_es') ?>",
            'successfully_processed': "<?= __('Successfully processed', 'moloni_es') ?>",
            'error_in_the_process': "<?= __('Error in the process', 'moloni_es') ?>",
        });
    });
</script>
