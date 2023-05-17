<?php

if (!defined('ABSPATH')) {
    exit;
}

use MoloniES\Controllers\Logs;
use MoloniES\Enums\LogLevel;

$logs = Logs::getAllAvailable();
?>

<div class="wrap">
    <h3><?= __('Here you can check all plugin logs', 'moloni_es') ?></h3>

    <div class="tablenav top">
        <div class="tablenav-pages">
            <?= Logs::getPagination() ?>
        </div>
    </div>

    <form method="post" action='<?= admin_url('admin.php?page=molonies&tab=logs') ?>'>
        <table class='wp-list-table widefat fixed striped posts'>
            <thead>
            <tr>
                <th><a><?= __('Date', 'moloni_es') ?></a></th>
                <th><a><?= __('Level', 'moloni_es') ?></a></th>
                <th><a><?= __('Message', 'moloni_es') ?></a></th>
                <th><a><?= __('Context', 'moloni_es') ?></a></th>
            </tr>
            <tr>
                <th>
                    <input
                        type="date"
                        class="inputOut ml-0"
                        name="filter_date"
                        value="<?= $_GET['filter_date'] ?? $_POST['filter_date'] ?? '' ?>"
                    >
                </th>
                <th>
                    <?php $options = LogLevel::getForRender() ?>

                    <select name="filter_level">
                        <?php $filterLevel = $_GET['filter_level'] ?? $_POST['filter_level'] ?? '' ?>

                        <option value='' selected><?=
                            __('Choose an option', 'moloni_es') ?>
                        </option>

                        <?php foreach ($options as $option) : ?>
                            <option
                                value='<?= $option['value'] ?>'
                                <?= $filterLevel === $option['value'] ? 'selected' : '' ?>
                            >
                            <?= $option['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_message"
                        value="<?= $_GET['filter_message'] ?? $_POST['filter_message'] ?? '' ?>"
                    >
                </th>
                <th>
                    <button type="submit" name="submit" id="submit" class="button button-primary">
                        <?= __('Search', 'moloni_es') ?>
                    </button>
                </th>
            </tr>
            </thead>

            <?php if (!empty($logs) && is_array($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td>
                            <?= $log['created_at'] ?>
                        </td>
                        <td>
                            <?php
                            $logLevel = $log['log_level'] ?? '';
                            ?>

                            <div class="chip <?= LogLevel::getClass($logLevel) ?>">
                                <?= LogLevel::getTranslation($logLevel) ?>
                            </div>
                        </td>
                        <td>
                            <?= $log['message'] ?>
                        </td>
                        <td>
                            <?php $logContext = htmlspecialchars($log['context']) ?>

                            <button type="button" class="button action"
                                    onclick="Moloni.Logs.openContextDialog(<?= $logContext ?>)">
                                <?= __("See", 'moloni_es') ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">
                        <?= __('No records found!', 'moloni_es') ?>
                    </td>
                </tr>
            <?php endif; ?>

            <tfoot>
            <tr>
                <th><a><?= __('Date', 'moloni_es') ?></a></th>
                <th><a><?= __('Level', 'moloni_es') ?></a></th>
                <th><a><?= __('Message', 'moloni_es') ?></a></th>
                <th><a><?= __('Context', 'moloni_es') ?></a></th>
            </tr>
            </tfoot>
        </table>
    </form>

    <div class="tablenav bottom">
        <div class="alignleft actions">
            <a class="button button-primary"
               href='<?= admin_url('admin.php?page=molonies&tab=logs&action=remLogs') ?>'>
                <?= __('Delete records older than 1 week', 'moloni_es') ?>
            </a>
        </div>

        <div class="tablenav-pages">
            <?= Logs::getPagination() ?>
        </div>
    </div>
</div>

<div id="logs-context-modal" class="modal" style="display: none">
    <h2>
        <?= __('Log context', 'moloni_es') ?>
    </h2>

    <pre id="logs-context-modal-content"></pre>

    <button type="button" class="button action">
        <?= __("Download", 'moloni_es') ?>
    </button>
</div>

<script>
    Moloni.Logs.init();
</script>
