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

        let form = $('.list_form');
        let actionButton = $('.button-start-imports');
        let searchButton = $('.search_button');
        let allProductCheckbox = $('.checkbox_create_product:enabled');
        let allStockCheckbox = $('.checkbox_update_stock_product:enabled');
        let productMaster = $('.checkbox_create_product_master');
        let stockMaster = $('.checkbox_update_stock_product_master');

        allProductCheckbox.add(allStockCheckbox).off('change').on('change', function () {
            dealWithMasters();
            dealWithActionButton();
        });

        actionButton.off('click').on('click', function () {
            doAction();
        });

        productMaster.off('click').on('click', function () {
            allProductCheckbox.prop('checked', $(this).prop("checked"));

            dealWithActionButton();
        });

        stockMaster.off('click').on('click', function () {
            allStockCheckbox.prop('checked', $(this).prop("checked"));

            dealWithActionButton();
        });

        searchButton.off('click').on('click', function () {
            $('#moloni input[type=hidden][name=paged]').val('1');
            form.submit();
        });

        dealWithMasters();
        dealWithActionButton();
    }

    function dealWithActionButton() {
        let actionButton = $('.button-start-imports');

        if ($('.checkbox_create_product:enabled:checked, .checkbox_update_stock_product:enabled:checked').length) {
            actionButton.removeAttr("disabled");
        } else {
            actionButton.attr("disabled", true);
        }
    }

    function dealWithMasters() {
        let enabledProducts = $(".checkbox_create_product:enabled");
        let enabledStocks = $(".checkbox_update_stock_product:enabled");

        if (enabledProducts.length) {
            $(".checkbox_create_product_master").prop('checked', $(".checkbox_create_product:enabled:checked").length === enabledProducts.length);
        }

        if (enabledStocks.length) {
            $(".checkbox_update_stock_product_master").prop('checked', $(".checkbox_update_stock_product:enabled:checked").length === enabledStocks.length);
        }
    }

    function doAction() {
        let rows = [];

        $('.checkbox_create_product:enabled:checked, .checkbox_update_stock_product:enabled:checked').each(function () {
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
