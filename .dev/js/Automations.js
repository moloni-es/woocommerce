if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Automations = (function($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        documentAutoChange();
    }

    function documentAutoChange() {
        toggleLineObserver('invoice_auto' , 'invoice_auto_status_line');
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