<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div>
    <div id="message" class="updated error is-dismissible">
        <p>
            <?=
                /** @var string $message */
                $message ?? ''
            ?>
        </p>

        <a onclick="showMoloniErrors();" style="cursor: pointer;">
            <p><?= __("Click here for more information",'moloni_es') ?></p>
        </a>

        <div class="MoloniConsoleLogError" style="display: none;">
            <b><?= __("Data",'moloni_es') ?>: </b>

            <br>

            <pre>
                <?=
                    /** @var array $data */
                    json_encode($data ?? [], JSON_PRETTY_PRINT)
                ?>
            </pre>
        </div>
    </div>
</div>

<script>
    function showMoloniErrors() {
        let errorConsole = document.getElementsByClassName("MoloniConsoleLogError");

        if (errorConsole.length > 0) {
            Array.from(errorConsole).forEach(function (element) {
                element.style['display'] = element.style['display'] === 'none' ? 'block' : 'none';
            });
        }
    }
</script>