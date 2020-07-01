<div class="header">
    <img src="<?= MOLONI_ES_IMAGES_URL ?>logo.png" width='300px' alt="MoloniES">
</div>

<?php settings_errors();?>

<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
    <a href="<?= esc_url(admin_url('admin.php?page=molonies')) ?>"
       class="nav-tab <?= (isset($_GET['tab'])) ?: 'nav-tab-active' ?>">
        <?= __('Orders','moloni_es') ?>
    </a>

    <a href="<?= esc_url(admin_url('admin.php?page=molonies&tab=settings')) ?>"
       class="nav-tab <?= ($_GET['tab'] === 'settings') ? 'nav-tab-active' : '' ?>">
        <?= __('Settings','moloni_es') ?>
    </a>

    <a href="<?= esc_url(admin_url('admin.php?page=molonies&tab=tools')) ?>"
       class="nav-tab <?= ($_GET['tab'] === 'tools') ? 'nav-tab-active' : '' ?>">
        <?= __('Tools','moloni_es') ?>
    </a>
</nav>

<?php
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';

switch ($tab) {
    case 'tools':
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/Tools.php';
        break;
    case 'settings':
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/Settings.php';
        break;
    default:
        include MOLONI_ES_TEMPLATE_DIR . 'Containers/PendingOrders.php';
        break;
}
