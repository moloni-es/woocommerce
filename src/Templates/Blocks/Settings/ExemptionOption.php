<?php

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? [];
$reasonName = $reasonName ?? '';
$reasonValue = $reasonValue ?? '';
?>

<?php if (isset($company['fiscalZone']['exemption']['reasons'])) : ?>
    <select id="<?= $reasonName ?>" name='opt[<?= $reasonName ?>]' class='inputOut'>
        <option value='' <?= empty($reasonValue) ? 'selected' : '' ?>>
            <?php esc_html_e('Choose an option', 'moloni_es') ?>
        </option>

        <?php foreach ($company['fiscalZone']['exemption']['reasons'] as $reason) : ?>
            <option
                value="<?= esc_html($reason['code']) ?>"
                title="<?= esc_html($reason['name']) ?>"
                <?= $reasonValue === $reason['code'] ? ' selected' : '' ?>
            >
                <?php echo esc_html("{$reason['code']} - {$reason['name']}"); ?>
            </option>
        <?php endforeach; ?>
    </select>
<?php else : ?>
    <input id="<?= $reasonName ?>"
           name="opt[<?= $reasonName ?>]"
           type="text"
           value="<?= $reasonValue ?>"
           class="inputOut"
    >
<?php endif; ?>
