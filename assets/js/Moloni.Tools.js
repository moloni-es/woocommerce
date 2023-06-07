if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Tools = (function($) {
    var doingAjax = false;

    function init() {
        startObservers();
    }

    function startObservers() {
        $('#importStockButton').on('click', showImportStockModal);
        $('#importProductsButton').on('click', showImportProductsModal);
        $('#exportStockButton').on('click', showExportStockModal);
        $('#exportProductsButton').on('click', showExportProductsModal);
    }

    //        Show Modal        //

    function showActionModal() {

    }

    function showImportStockModal() {
        alert('showImportStockModal');
    }

    function showImportProductsModal() {
        alert('showImportProductsModal');
    }

    function showExportStockModal() {
        alert('showExportStockModal');
    }

    function showExportProductsModal() {
        alert('showExportProductsModal');
    }

    //        Modal observers        //

    //        Requests        //

    //        Auxiliary        //

    return {
        init: init
    }
}(jQuery));
