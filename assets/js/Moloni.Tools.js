//       Page       //

if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Tools = (function ($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        $('#importStocksButton').on('click', Moloni.Tools.overlays.importStocks);
        $('#importProductsButton').on('click', Moloni.Tools.overlays.importProducts);
        $('#exportStocksButton').on('click', Moloni.Tools.overlays.exportStocks);
        $('#exportProductsButton').on('click', Moloni.Tools.overlays.exportProducts);
    }

    return {
        init: init
    }
}(jQuery));

//       Overlays       //

if (Moloni.Tools.overlays === undefined) {
    Moloni.Tools.overlays = {};
}

Moloni.Tools.overlays.importStocks = (function () {
    var doingAjax = false;
    var $ = jQuery;
    var importStocksModal = $('#import-stocks-modal');

    importStocksModal.modal({
        fadeDuration: 100,
        escapeClose: false,
        clickClose: false,
        showClose: true
    });

    function doAction() {
        alert('oi');
    }

    importStocksModal.find('.button-primary').on('click', doAction);
});

Moloni.Tools.overlays.importProducts = (function () {
    var doingAjax = false;
    var $ = jQuery;
    var importProductsModal = $('#import-products-modal');

    importProductsModal.modal({
        fadeDuration: 100,
        escapeClose: false,
        clickClose: false,
        showClose: true
    });

    function doAction() {
        alert('oi');
    }

    importProductsModal.find('.button-primary').on('click', doAction);
});

Moloni.Tools.overlays.exportStocks = (function () {
    var doingAjax = false;
    var $ = jQuery;
    var exportStocksModal = jQuery('#export-stocks-modal');

    exportStocksModal.modal({
        fadeDuration: 100,
        escapeClose: false,
        clickClose: false,
        showClose: true
    });

    function doAction() {
        alert('oi');
    }

    exportStocksModal.find('.button-primary').on('click', doAction);
});

Moloni.Tools.overlays.exportProducts = (function () {
    var doingAjax = false;
    var $ = jQuery;
    var exportProductsModal = $('#export-products-modal');

    exportProductsModal.modal({
        fadeDuration: 100,
        escapeClose: false,
        clickClose: false,
        showClose: true
    });

    function doAction() {
        alert('oi');
    }

    exportProductsModal.find('.button-primary').on('click', doAction);
});
