<div id="action-modal" class="modal" style="display: none">
    <h2>
        <?= __('Action in progress', 'moloni_es') ?>
    </h2>
    <div id="action-modal-content" style="display: none;"></div>

    <div id="action-modal-spinner" style="display: none;">
        <p>
            <?= __('We are processing your request.', 'moloni_es') ?>
        </p>

        <img src="<?php echo esc_url( includes_url() . 'js/thickbox/loadingAnimation.gif' ); ?>" />

        <p>
            <?= __('Please wait until the process finishes!', 'moloni_es') ?>
        </p>
    </div>

    <div id="action-modal-error" style="display: none;">
        <p>
            <?= __('Something went wrong!', 'moloni_es') ?>
        </p>
        <p>
            <?= __('Please check logs for more information.', 'moloni_es') ?>
        </p>
    </div>

    <div>
        <a class="button button-large button-secondary" href="#" rel="modal:close" style="display: none;">
            <?= __('Close', 'moloni_es') ?>
        </a>
    </div>
</div>
