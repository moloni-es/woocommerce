<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php use \MoloniES\Controllers\Documents; ?>
<?php use \MoloniES\Controllers\PendingOrders; ?>

<?php
/** @var WC_Order[] $orders */
$orders = PendingOrders::getAllAvailable();
?>

<div class="wrap">
    <?php if (isset($document) && $document instanceof Documents && $document->getError()) : ?>
        <?php $document->getError()->showError(); ?>
    <?php endif; ?>

    <h3><?= __('Here you can see all the orders you have to generate' , 'moloni_es') ?></h3>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text"></label><select
                    name="action" id="bulk-action-selector-top">
                <option value="-1"><?= __('Bulk actions', 'moloni_es') ?></option>
                <option value="bulkGenInvoice"><?= __('Generate documents', 'moloni_es') ?></option>
            </select>
            <input type="submit" id="doAction" class="button action" value="<?= __('Run', 'moloni_es') ?>">
        </div>

        <div class="tablenav-pages">
            <?= PendingOrders::getPagination() ?>
        </div>
    </div>

    <table class='wp-list-table widefat fixed striped posts'>
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
                        <a target="_blank" href=<?= $order->get_edit_order_url() ?>>#<?= $order->get_order_number() ?></a>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_billing_first_name())) {
                            echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        } else {
                            echo __('Unknown','moloni_es');
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
                                <option value='invoice' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoice' ? 'selected' : '') ?>>
                                    <?= __('Invoice' , 'moloni_es') ?>
                                </option>

                                <option value='invoiceReceipt' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoiceReceipt' ? 'selected' : '') ?>>
                                    <?= __('Invoice + Receipt' , 'moloni_es') ?>
                                </option>

                                <option value='simplifiedInvoice'<?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'simplifiedInvoice' ? 'selected' : '') ?>>
                                    <?= __('Simplified Invoice' , 'moloni_es') ?>
                                </option>

                                <option value='billsOfLading' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'billsOfLading' ? 'selected' : '') ?>>
                                    <?= __('Bill of lading' , 'moloni_es') ?>
                                </option>

                                <option value='purchaseOrder' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'purchaseOrder' ? 'selected' : '') ?>>
                                    <?= __('Purchase Order' , 'moloni_es') ?>
                                </option>

                                <option value='proFormaInvoice' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'proFormaInvoice' ? 'selected' : '') ?>>
                                    <?= __('Pro Forma Invoice' , 'moloni_es') ?>
                                </option>
                            </select>

                            <input type="submit"
                                   class="wp-core-ui button-primary"
                                   style="width: 80px; text-align: center; margin-right: 5px"
                                   value="<?= __('Create' , 'moloni_es') ?>"
                            >


                            <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                               href="<?= esc_url(admin_url('admin.php?page=molonies&action=remInvoice&id=' . $order->get_id())) ?>">
                                <?= __('Remove' , 'moloni_es') ?>
                            </a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php else : ?>
            <tr>
                <td colspan="8">
                    <?= __('No orders to be generated were found!','moloni_es') ?>
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

            <th><a><?= __('Order' , 'moloni_es') ?></a></th>
            <th><a><?= __('Client' , 'moloni_es') ?></a></th>
            <th><a><?= __('VAT' , 'moloni_es') ?></a></th>
            <th><a><?= __('Total' , 'moloni_es') ?></a></th>
            <th><a><?= __('Status' , 'moloni_es') ?></a></th>
            <th><a><?= __('Payment date' , 'moloni_es') ?></a></th>
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

<div id="bulk-action-progress-modal" class="modal" style="display: none">
    <div id="bulk-action-progress-content">
        <h2>
            <?= __('Generating ' , 'moloni_es') ?>
            <span id="bulk-action-progress-current">0</span>
            <?= __(' of ' , 'moloni_es')?>
            <span id="bulk-action-progress-total">0</span>
            <?= __(' documents.' , 'moloni_es')?>
        </h2>
        <div id="bulk-action-progress-message">
        </div>
    </div>
</div>
