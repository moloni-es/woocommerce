if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Settings = (function($) {
    var translations = {};

    function init(_translations) {
        translations = _translations;

        startObservers();
    }

    function startObservers() {
        prefixChange();

        documentStatusChange();
        documentTypeChange();

        shippingInfoChange();
        loadAddressChange();
    }

    function prefixChange() {
        var prefixPreview = $('#prefix_preview');
        var clientPrefix = $('#client_prefix');

        if (!prefixPreview.length || !clientPrefix.length) {
            return;
        }

        clientPrefix.on('change', function () {
            prefixPreview.text('(' + translations.example + ': ' + clientPrefix.val() + ')');
        });
    }

    function documentStatusChange() {
        toggleLineObserver('document_status' , 'create_bill_of_lading_line');
    }

    function documentTypeChange() {
        var documentTypeInput = $('#document_type');
        var documentStatusInput = $('#document_status');
        var createBillOfLadingInput = $('#create_bill_of_lading');

        if (!documentTypeInput.length || !documentStatusInput.length || !createBillOfLadingInput.length) {
            return;
        }

        documentTypeInput.on('change', function () {
            if (documentTypeInput.val() === 'invoiceAndReceipt') {
                documentStatusInput
                    .val(1)
                    .prop("disabled", true)
                    .trigger('change');
            } else {
                documentStatusInput
                    .prop("disabled", false);
            }

            if (['billsOfLading', 'estimate'].includes(documentTypeInput.val())) {
                createBillOfLadingInput
                    .val(0)
                    .prop("disabled", true)
                    .trigger('change');
            } else {
                createBillOfLadingInput
                    .prop("disabled", false);
            }
        });

        documentTypeInput.trigger('change');
    }

    function shippingInfoChange() {
        toggleLineObserver('shipping_info' , 'load_address_line');
    }

    function loadAddressChange() {
        toggleLineObserver('load_address' , 'load_address_custom_line');
    }

    //      Auxiliary      //

    function toggleLineObserver(inputId, lineId) {
        var input = $('#' + inputId);
        var line = $('#' + lineId);

        if (!input.length || !line.length) {
            return;
        }

        input.on('change', function () {
            parseInt(input.val()) === 1 ? line.show(200) : line.hide(200);
        });

        input.trigger('change');
    }

    return {
        init: init
    }
}(jQuery));
