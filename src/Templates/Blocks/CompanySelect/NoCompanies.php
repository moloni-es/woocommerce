<?php

use MoloniES\Enums\Domains;

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="companies-invalid">
    <img src="<?= MOLONI_ES_IMAGES_URL ?>no_companies.svg" width='150px' alt="Moloni">

    <div class="companies-invalid__title">
        <?= __('You do not have any valid company to use the plugin', 'moloni_es') ?>
    </div>

    <div class="companies-invalid__message">
        <?= __('Please confirm that your account has access to an active company with a plan that allows you to access the plugins.', 'moloni_es') ?>
    </div>

    <div class="companies-invalid__help">
        <?= __('Learn more about our plans at: ', 'moloni_es') ?>
        <a href="https://www.moloni.es/plansandprices" target="_blank">
            <?= Domains::PLANS ?>
        </a>
    </div>

    <button class="ml-button ml-button--primary" onclick="window.location.href = 'admin.php?page=molonies&action=logout'">
        <?= __('Back to login', 'moloni_es') ?>
    </button>
</div>
