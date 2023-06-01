<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div>
    <div id="message" class="updated error is-dismissible">
        <p><?= $message ?></p>
        <a onclick="showMoloniErrors();" style="cursor: pointer;">
            <p><?= __("Click here for more information",'moloni_es') ?></p>
        </a>

        <div class="MoloniConsoleLogError" style="display: none;">
            <b><?= __("Endpoint",'moloni_es') ?>: </b> <?= $url ?>
            <br>
            
            <b><?= __("Data received: ",'moloni_es') ?></b>
            <br/>
            <pre><?= /** @var array $received */
                print_r($received, true) ?>
            </pre>

            <b><?= __("Data sent: ",'moloni_es') ?></b>
            <br/>
            <pre><?= /** @var array $sent */
                print_r($sent, true) ?>
            </pre>
        </div>
    </div>
</div>

<script>
    function showMoloniErrors() {
        var errorConsole = [];
        errorConsole = document.getElementsByClassName("MoloniConsoleLogError");
        if (errorConsole.length > 0) {
            Array.from(errorConsole).forEach(function (element) {
                element.style['display'] = element.style['display'] === 'none' ? 'block' : 'none';
            });
        }
    }
</script>