<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php use MoloniES\Log; ?>

<br>
<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">
    <tr>
        <th style="padding: 2rem">
            <strong class="name"><?= __('Force stock synchronization' , 'moloni_es') ?></strong>
            <p class='description'><?= __('Synchronize stocks of all items used in the last 7 days' , 'moloni_es') ?></p>
        </th>
        <td class="run-tool" style="padding: 2rem; text-align: right">
            <a class="button button-large"
               href='<?= esc_url(admin_url('admin.php?page=molonies&tab=tools&action=syncStocks&since=' . gmdate('Y-m-d', strtotime("-1 week")))) ?>'>
                <?= __('Force stock synchronization' , 'moloni_es') ?>
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