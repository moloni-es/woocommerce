<div class='outBoxEmpresa'>
    <h2><?= __("Welcome! Here you can select which company you want to connect with WooCoommerce" , 'moloni_es') ?></h2>
    <?php if (isset($companies) && is_array($companies)) : ?>
        <?php foreach ($companies as $key => $company) : ?>
            <div class="caixaLoginEmpresa"
                 onclick="window.location.href = 'admin.php?page=molonies&companyId=<?= $company["companyId"] ?>'"
                 title="<?= __("Login" , 'moloni_es') ?> <?= $company["name"] ?>">

                <span><b><?= $company["name"] ?></b></span>
                <br><?= $company["address"] ?>
                <br><?= $company["zipCode"] ?>
                <p><b><?= __("VAT" , 'moloni_es') ?>: </b><?= $company["vat"] ?></p></div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>