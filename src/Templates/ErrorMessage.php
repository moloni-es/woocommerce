<div style="margin-top: 50px">
    <div id="message" class="updated error is-dismissible">
        <p><?= $message ?></p>
        <a onclick="showMoloniErrors()" style="cursor: pointer;">
            <p><?= __("Click here for more information",'moloni_es') ?></p>
        </a>

        <div id="MoloniConsoleLogError" style="display: none">
            <p>
                <b><?= __("Endpoint",'moloni_es') ?>: </b> <?= $url ?>
            </p>
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
        var errorConsole = document.getElementsByClassName("MoloniConsoleLogError");
        if (errorConsole.length > 0) {
            errorConsole.forEach(function (element, index) {
                element.style['display'] = errorConsole.style['display'] === 'none' ? 'block' : 'none';
            });
        }
    }
</script>