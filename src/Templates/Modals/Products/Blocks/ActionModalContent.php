<p>
    <?= __('Total results', 'moloni_es') ?>: <?= $data['totalResults'] ?? 0 ?>
</p>

<?php if (isset($data['hasMore']) && $data['hasMore']) : ?>
    <p>
        <?= min($data['currentPercentage'], 100) ?>%
    </p>

    <img src="<?php echo esc_url(includes_url() . 'js/thickbox/loadingAnimation.gif'); ?>"/>

    <p>
        <?= __('Please wait, tool in progress', 'moloni_es') ?>
    </p>
<?php else: ?>
    <p>
        <?= __('Process complete', 'moloni_es') ?>
    </p>
<?php endif; ?>
