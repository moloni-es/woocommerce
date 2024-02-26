<?php
if (!defined('ABSPATH')) {
    exit;
}

$hasValidCompany = false;
?>

<section id="moloni" class="moloni">
    <?php include MOLONI_ES_TEMPLATE_DIR . '/Assets/Fonts.php' ?>

    <div class="companies">
        <?php if (!empty($companies) && is_array($companies)) : ?>
            <div class="companies__title">
                <?= __("Select the company you want to connect with WooCommerce", 'moloni_es') ?>
            </div>

            <div class="companies__list">
                <?php
                foreach ($companies as $company) {
                    include MOLONI_ES_TEMPLATE_DIR . 'Blocks/CompanySelect/CompanyCard.php';
                }
                ?>
            </div>
        <?php else : ?>
            <?php include MOLONI_ES_TEMPLATE_DIR . 'Blocks/CompanySelect/NoCompanies.php'; ?>
        <?php endif; ?>
    </div>

    <script>
        jQuery(document).ready(function () {

        });
    </script>
</section>
