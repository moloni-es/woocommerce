<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php if (!empty($errorData) && is_array($errorData)): ?>
    <pre style="display: none;" id="curl_error_data">
            <?= print_r($errorData, true) ?>
        </pre>
<?php endif; ?>

<div id='formLogin'>
    <a href='<?= esc_url( 'https://moloni.es' ); ?>' target='_BLANK'>
        <img src="<?= MOLONI_ES_IMAGES_URL ?>logo.svg" width='300px' alt="Moloni">
    </a>
    <hr>
    <form id='formPerm' method='POST' action='<?= admin_url('admin.php?page=molonies') ?>'>
        <table>
            <tr>
                <td><label for='developer_id'><?= __('Developer Id','moloni_es') ?></label></td>
                <td><input id="developer_id" type='text' name='developer_id'></td>
            </tr>

            <tr>
                <td><label for='client_secret'><?= __('Client Secret','moloni_es') ?></label></td>
                <td><input id="client_secret" type='text' name='client_secret'></td>
            </tr>

            <?php if (!empty($errorMessage) && is_string($errorMessage)): ?>
                <tr>
                    <td></td>
                    <td>
                        <b><?= $errorMessage ?></b>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <td></td>
                <td>
                    <div>
                        <input type='submit' name='submit' value='<?= __('Connect with Moloni','moloni_es') ?>'>
                        <span class='goRight power'>
                            <a href="<?= esc_url( 'https://woocommerce.moloni.es' ); ?>" target="_blank">
                                <?= __('Click here for more instructions','moloni_es') ?>
                            </a>
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </form>
</div>
