<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="header">
    <img src="<?= MOLONI_ES_IMAGES_URL ?>logo.svg" width='300px' alt="Moloni">
</div>

<?php settings_errors();?>

<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
    <?php
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : '';
    ?>

    <a href="<?= esc_url(admin_url('admin.php?page=molonies')) ?>"
       class="nav-tab <?=  ($tab === '') ? 'nav-tab-active' : '' ?>">
        <?= __('Orders','moloni_es') ?>
    </a>

    <a href="<?= esc_url(admin_url('admin.php?page=molonies&tab=settings')) ?>"
       class="nav-tab <?= ($tab === 'settings') ? 'nav-tab-active' : '' ?>">
        <?= __('Settings','moloni_es') ?>
    </a>

    <a href="<?= esc_url(admin_url('admin.php?page=molonies&tab=automation')) ?>"
       class="nav-tab <?= ($tab === 'automation') ? 'nav-tab-active' : '' ?>">
        <?= __('Automation','moloni_es') ?>
    </a>

    <a href="<?= esc_url(admin_url('admin.php?page=molonies&tab=logs')) ?>"
       class="nav-tab <?= $tab === 'logs' ? 'nav-tab-active' : '' ?>">
        <?= __('Logs','moloni_es') ?>
    </a>

    <a href="<?= esc_url(admin_url('admin.php?page=molonies&tab=tools')) ?>"
       class="nav-tab <?= ($tab === 'tools') ? 'nav-tab-active' : '' ?>">
        <?= __('Tools','moloni_es') ?>
    </a>

</nav>

<?php

if (isset($pluginErrorException) && $pluginErrorException instanceof \MoloniES\Exceptions\Error) {
    $pluginErrorException->showError();
}

switch ($tab) {
    case 'tools':
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/Tools.php';
        break;
    case 'automation':
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/Automation.php';
        break;
    case 'settings':
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/Settings.php';
        break;
    case 'logs':
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/Logs.php';
        break;
    default:
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/PendingOrders.php';
        break;
}
