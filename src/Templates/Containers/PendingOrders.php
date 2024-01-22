<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php

use MoloniES\Enums\DocumentTypes;
use MoloniES\Models\PendingOrders;

?>

<?php
/** @var WC_Order[] $orders */
$orders = PendingOrders::getAllAvailable();
?>

<div class="wrap">
    <h3><?= __('Here you can see all the orders you have to generate', 'moloni_es') ?></h3>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text"></label><select
                    name="action" id="bulk-action-selector-top">
                <option value="-1"><?= __('Bulk actions', 'moloni_es') ?></option>
                <option value="bulkGenInvoice"><?= __('Generate documents', 'moloni_es') ?></option>
                <option value="bulkDiscardOrder"><?= __('Discard documents', 'moloni_es') ?></option>
            </select>
            <input type="submit" id="doAction" class="button action" value="<?= __('Run', 'moloni_es') ?>">
        </div>

        <div class="tablenav-pages">
            <?= PendingOrders::getPagination() ?>
        </div>
    </div>

    <table class='wp-list-table widefat striped posts'>
        <thead>
        <tr>
            <td class="manage-column column-cb check-column">
                <label for="moloni-pending-orders-select-all" class="screen-reader-text"></label>
                <input id="moloni-pending-orders-select-all" class="moloni-pending-orders-select-all" type="checkbox">
            </td>
            <th><a><?= __('Order', 'moloni_es') ?></a></th>
            <th><a><?= __('Client', 'moloni_es') ?></a></th>
            <th><a><?= __('VAT', 'moloni_es') ?></a></th>
            <th><a><?= __('Total', 'moloni_es') ?></a></th>
            <th><a><?= __('Status', 'moloni_es') ?></a></th>
            <th><a><?= __('Payment date', 'moloni_es') ?></a></th>
            <th style="width: 350px;"></th>
        </tr>
        </thead>

        <?php if (!empty($orders) && is_array($orders)) : ?>

            <!-- Let's draw a list of all the available orders -->
            <?php foreach ($orders as $order) : ?>
                <tr id="moloni-pending-order-row-<?= $order->get_id() ?>">
                    <td class="">
                        <label for="moloni-pending-order-<?= $order->get_id() ?>" class="screen-reader-text"></label>
                        <input id="moloni-pending-order-<?= $order->get_id() ?>" type="checkbox"
                               value="<?= $order->get_id() ?>">
                    </td>
                    <td>
                        <a target="_blank"
                           href=<?= $order->get_edit_order_url() ?>>#<?= $order->get_order_number() ?></a>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_billing_first_name())) {
                            echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        } else {
                            echo __('Unknown', 'moloni_es');
                        }
                        ?>
                    <td>
                        <?php
                        $vat = '';

                        if (defined('VAT_FIELD')) {
                            $meta = $order->get_meta(VAT_FIELD);

                            $vat = $meta;
                        }

                        echo empty($vat) ? 'n/a' : $vat;
                        ?>
                    </td>
                    <td><?= $order->get_total() . $order->get_currency() ?></td>
                    <td>
                        <?php
                        $availableStatus = wc_get_order_statuses();
                        $needle = 'wc-' . $order->get_status();

                        if (isset($availableStatus[$needle])) {
                            echo $availableStatus[$needle];
                        } else {
                            echo $needle;
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_date_paid())) {
                            echo date('Y-m-d H:i:s', strtotime($order->get_date_paid()));
                        } else {
                            echo 'n/a';
                        }
                        ?>
                    </td>
                    <td class="order_status column-order_status" style="text-align: right">
                        <form action="<?= admin_url('admin.php') ?>">
                            <input type="hidden" name="page" value="molonies">
                            <input type="hidden" name="action" value="genInvoice">
                            <input type="hidden" name="id" value="<?= $order->get_id() ?>">

                            <select name="document_type" style="margin-right: 5px; max-width: 45%;">
                                <?php
                                $documentType = '';

                                if (defined('DOCUMENT_TYPE') && !empty(DOCUMENT_TYPE)) {
                                    $documentType = DOCUMENT_TYPE;
                                }
                                ?>

                                <?php foreach (DocumentTypes::getForRender() as $id => $name) : ?>
                                    <option value='<?= $id ?>' <?= ($documentType === $id ? 'selected' : '') ?>>
                                        <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="submit"
                                   class="wp-core-ui button-primary"
                                   style="width: 80px; text-align: center; margin-right: 5px"
                                   value="<?= __('Create', 'moloni_es') ?>"
                            >


                            <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                               href="<?= esc_url(admin_url('admin.php?page=molonies&action=remInvoice&id=' . $order->get_id())) ?>">
                                <?= __('Discard', 'moloni_es') ?>
                            </a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="8">
                    <?= __('No orders to be generated were found!', 'moloni_es') ?>
                </td>
            </tr>

        <?php endif; ?>

        <tfoot>
        <tr>
            <td class="manage-column column-cb check-column">
                <label for="moloni-pending-orders-select-all-bottom" class="screen-reader-text"></label>
                <input id="moloni-pending-orders-select-all-bottom" class="moloni-pending-orders-select-all"
                       type="checkbox">
            </td>

            <th><a><?= __('Order', 'moloni_es') ?></a></th>
            <th><a><?= __('Client', 'moloni_es') ?></a></th>
            <th><a><?= __('VAT', 'moloni_es') ?></a></th>
            <th><a><?= __('Total', 'moloni_es') ?></a></th>
            <th><a><?= __('Status', 'moloni_es') ?></a></th>
            <th><a><?= __('Payment date', 'moloni_es') ?></a></th>
            <th></th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?= PendingOrders::getPagination() ?>
        </div>
    </div>
</div>

<?php include MOLONI_ES_TEMPLATE_DIR . 'Modals/PendingOrders/BulkActionModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.OrdersBulkAction({
            startingProcess: "<?=__('Starting process...', 'moloni_es')?>",
            noOrdersSelected: "<?=__('No orders selected to process', 'moloni_es')?>",
            creatingDocument: "<?=__('Creating document', 'moloni_es')?>",
            discardingOrder: "<?=__('Discarding order', 'moloni_es')?>",
            createdDocuments: "<?=__('Documents created:', 'moloni_es')?>",
            documentsWithErrors: "<?=__('Documents with errors:', 'moloni_es')?>",
            discardedOrders: "<?=__('Orders discarded:', 'moloni_es')?>",
            ordersWithErrors: "<?=__('Orders with errors:', 'moloni_es')?>",
            close: "<?=__('Close', 'moloni_es')?>",
        });
    });
</script>
