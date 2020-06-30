<?php use \MoloniES\Controllers\Documents; ?>
<?php use \MoloniES\Controllers\PendingOrders; ?>

<?php $orders = PendingOrders::getAllAvailable(); ?>

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

            <!-- Lets draw a list of all the available orders -->
            <?php foreach ($orders as $order) : ?>
                <tr id="moloni-pending-order-row-<?= $order['id'] ?>">
                    <td class="">
                        <label for="moloni-pending-order-<?= $order['id'] ?>" class="screen-reader-text"></label>
                        <input id="moloni-pending-order-<?= $order['id'] ?>" type="checkbox"
                               value="<?= $order['id'] ?>">
                    </td>
                    <td>
                        <a href=<?= admin_url('post.php?post=' . $order['id'] . '&action=edit') ?>>#<?= $order['number'] ?></a>
                    </td>
                    <td>
                        <?php
                        if (isset($order['info']['_billing_first_name']) && !empty($order['info']['_billing_first_name'])) {
                            echo $order['info']['_billing_first_name'] . ' ' . $order['info']['_billing_last_name'];
                        } else {
                            echo __('Unknown','moloni_es');
                        }

                        ?>
                    <td><?= (isset($order['info'][VAT_FIELD]) && !empty($order['info'][VAT_FIELD])) ? $order['info'][VAT_FIELD] : 'n/a' ?></td>
                    <td><?= $order['info']['_order_total'] . $order['info']['_order_currency'] ?></td>
                    <td><?= $order['status'] ?></td>
                    <td><?= $order['info']['_completed_date'] ?></td>
                    <td class="order_status column-order_status" style="text-align: right">
                        <form action="<?= admin_url('admin.php') ?>">
                            <input type="hidden" name="page" value="molonies">
                            <input type="hidden" name="action" value="genInvoice">
                            <input type="hidden" name="id" value="<?= $order['id'] ?>">

                            <select name="document_type" style="margin-right: 5px">
                                <option value='invoice' <?= (DOCUMENT_TYPE === 'invoice' ? 'selected' : '') ?>>
                                    <?= __('Invoice' , 'moloni_es') ?>
                                </option>

                                <option value='invoiceReceipt' <?= (DOCUMENT_TYPE === 'invoiceReceipt' ? 'selected' : '') ?>>
                                    <?= __('Invoice + Receipt' , 'moloni_es') ?>
                                </option>

                                <option value='simplifiedInvoice'<?= (DOCUMENT_TYPE === 'simplifiedInvoice' ? 'selected' : '') ?>>
                                    <?= __('Simplified Invoice' , 'moloni_es') ?>
                                </option>

                                <option value='billsOfLading' <?= (DOCUMENT_TYPE === 'billsOfLading' ? 'selected' : '') ?>>
                                    <?= __('Bill of lading' , 'moloni_es') ?>
                                </option>

                                <option value='purchaseOrder' <?= (DOCUMENT_TYPE === 'purchaseOrder' ? 'selected' : '') ?>>
                                    <?= __('Purchase Order' , 'moloni_es') ?>
                                </option>

                                <option value='proFormaInvoice' <?= (DOCUMENT_TYPE === 'proFormaInvoice' ? 'selected' : '') ?>>
                                    <?= __('Pro Forma Invoice' , 'moloni_es') ?>
                                </option>
                            </select>

                            <input type="submit"
                                   class="wp-core-ui button-primary"
                                   style="width: 80px; text-align: center; margin-right: 5px"
                                   value="<?= __('Create' , 'moloni_es') ?>"
                            >


                            <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                               href="<?= admin_url('admin.php?page=molonies&action=remInvoice&id=' . $order['id']) ?>">
                                <?= __('Remove' , 'moloni_es') ?>
                            </a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php else : ?>
            <tr>
                <td colspan="7">
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
