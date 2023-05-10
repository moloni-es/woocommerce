if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Settings = (function($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        documentStatusChange();

        shippingInfoChange();
        loadAddressChange();
    }

    function documentStatusChange() {
        toggleLineObserver('document_status' , 'create_bill_of_lading_line');
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