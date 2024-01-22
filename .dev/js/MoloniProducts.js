if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.MoloniProducts === undefined) {
    Moloni.MoloniProducts = {};
}

Moloni.MoloniProducts = (function ($) {
    var translations;

    function init(_translations) {
        translations = _translations || {};

        startObservers();
    }

    function startObservers() {
        $('#importStocksButton').on('click', function () {
            showPreModal('import-stocks-modal', 'toolsMassImportStock');
        });

        $('#importProductsButton').on('click', function () {
            showPreModal('import-products-modal', 'toolsMassImportProduct');
        });

        let allSelectedCheckboxQuery = '.checkbox_create_product:enabled:checked, .checkbox_update_stock_product:enabled:checked';
        let actionButton = $('.button-start-imports');

        $('.checkbox_create_product:enabled, .checkbox_update_stock_product:enabled').off('change').on('change', function () {
            if ($(allSelectedCheckboxQuery).length) {
                actionButton.removeAttr("disabled");
            } else {
                actionButton.attr("disabled", true);
            }
        }).trigger('change');

        actionButton.off('click').on('click', function () {
            let rows = [];

            $(allSelectedCheckboxQuery).each(function () {
                rows.push($(this));
            });

            if (rows.length) {
                Moloni.modals.ProductsProcessBulk(
                    rows,
                    'toolsCreateWcProduct',
                    'toolsUpdateWcStock',
                    translations,
                    startObservers
                );
            }
        });
    }

    function showPreModal(modalId, action) {
        const preModal = $('#' + modalId);

        preModal.modal({
            fadeDuration: 100,
            escapeClose: false,
            clickClose: false,
            showClose: true
        });

        preModal.find('.button-primary').off('click').on('click', function () {
            Moloni.modals.ProductsProcessAll(action);
        });
    }

    return {
        init: init,
    }
}(jQuery));
