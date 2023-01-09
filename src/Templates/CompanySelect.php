<?php
if (!defined('ABSPATH')) {
    exit;
}

$hasValidCompany = false;
?>

<?php if (isset($companies) && is_array($companies)) : ?>
    <div class='outBoxEmpresa'>
        <?php foreach ($companies as $key => $company) : ?>
            <!-- First valid company, so we need to render the title -->
            <?php if ($hasValidCompany === false) : ?>
                <h2>
                    <?= __("Welcome! Here you can select which company you want to connect with WooCoommerce" , 'moloni_es') ?>
                </h2>
            <?php endif; ?>

            <?php $hasValidCompany = true;?>

            <div class="caixaLoginEmpresa"
                 onclick="window.location.href = 'admin.php?page=molonies&companyId=<?= $company["companyId"] ?>'"
                 title="<?= __("Login/Entrar") ?> <?= $company["name"] ?>">

                    <span>
                        <b><?= $company["name"] ?></b>
                    </span>
                <br>

                <?= $company["address"] ?>
                <br>

                <?= $company["zipCode"] ?>

                <p>
                    <b><?= __("Contribuinte") ?>: </b><?= $company["vat"] ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$hasValidCompany) : ?>
    <div class="no-companies__wrapper">
        <img src="<?= MOLONI_ES_IMAGES_URL ?>no_companies.svg" width='150px' alt="Moloni">

        <div class="no-companies__title">
            <?= __('You do not have any valid company to use the plugin') ?>
        </div>

        <div class="no-companies__message">
            <?= __('Please confirm that your account has access to an active company with a plan that allows you to access the plugins.') ?>
        </div>

        <div class="no-companies__help">
            <?= __('Learn more about our plans at: ') ?>
            <a href="https://www.moloni.es/plansandprices" target="_blank">https://www.moloni.es/plansandprices</a>
        </div>

        <button class="button button-primary"
                onclick="window.location.href = 'admin.php?page=molonies&action=logout'">
            <?= __('Back to login') ?>
        </button>
    </div>
<?php endif; ?>