<div id="export-stocks-modal" class="modal" style="display: none">
    <h2>
        <?= __('Export stocks to Moloni' , 'moloni_es') ?>
    </h2>
    <div>
        <p>
            <?= __('This tool will cycle for all your WooCommerce products and will insert manual stock movements in your Moloni account to make sure the stocks are equal in both platforms.', 'moloni_es') ?>
        </p>
        <p>
            <?= __('When the tool is finish, all your Moloni stock will be updated to match the stock on your WooCommerce store.', 'moloni_es') ?>
        </p>
        <p>
            <?= __('This may take a while, so, please keep this window open until the process finishes.', 'moloni_es') ?>
        </p>
        <p>
            <?= __('Are you sure you want to continue?', 'moloni_es') ?>
        </p>
    </div>
    <div>
        <a class="button button-large button-secondary" href="#" rel="modal:close">
            <?= __('Close', 'moloni_es') ?>
        </a>
        <a class="button button-large button-primary">
            <?= __('Start', 'moloni_es') ?>
        </a>
    </div>
</div>
