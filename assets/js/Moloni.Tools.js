//       Page       //

if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Tools = (function ($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        $('#importStocksButton').on('click', function () {
            showPreModal('import-stocks-modal', 'toolsImportStock');
        });
        $('#importProductsButton').on('click', function () {
            showPreModal('import-products-modal', 'toolsImportProduct');
        });
        $('#exportStocksButton').on('click', function () {
            showPreModal('export-stocks-modal', 'toolsExportStock');
        });
        $('#exportProductsButton').on('click', function () {
            showPreModal('export-products-modal', 'toolsExportProduct');
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
            Moloni.Tools.modals.syncTool(action);
        });
    }

    return {
        init: init
    }
}(jQuery));

//       Overlays       //

if (Moloni.Tools.modals === undefined) {
    Moloni.Tools.modals = {};
}

Moloni.Tools.modals.syncTool = (async function (action) {
    const $ = jQuery;

    const actionModal = $('#action-modal');

    const closeButton = actionModal.find('.button-secondary');
    const spinner = actionModal.find('#action-modal-spinner');
    const content = actionModal.find('#action-modal-content');
    const error = actionModal.find('#action-modal-error');

    let page = 1;

    content.html('').hide();
    closeButton.hide();
    error.hide();
    spinner.show();

    const toogleContent = () => {
        spinner.fadeOut(100, function () {
            content.fadeIn(200);
        });
    }

    const sync = async () => {
        const resp = await $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                'action': action,
                'page': page
            },
            async: true
        });

        if (page === 1) {
            toogleContent();
        }

        content.html(resp.overlayContent || '---');

        if (resp.hasMore && actionModal.is(':visible')) {
            page = page + 1;

            return await sync();
        }
    }

    actionModal.modal({
        fadeDuration: 0,
        escapeClose: false,
        clickClose: false,
        closeExisting: true,
    });

    try {
        await sync();
    } catch (ex) {
        spinner.fadeOut(50);
        content.fadeOut(50);
        error.fadeIn(200);
    }

    closeButton.show(200);
});
