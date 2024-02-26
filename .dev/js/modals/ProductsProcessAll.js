if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.modals === undefined) {
    Moloni.modals = {};
}

Moloni.modals.ProductsProcessAll = (async function (action) {
    const $ = jQuery;

    const actionModal = $('#action-modal');

    const closeButton = actionModal.find('.button-secondary');
    const spinner = actionModal.find('#action-modal-spinner');
    const content = actionModal.find('#action-modal-content');
    const error = actionModal.find('#action-modal-error');
    const titleStart = actionModal.find('#action-modal-title-start');
    const titleEnd = actionModal.find('#action-modal-title-end');

    let page = 1;

    content.html('').hide();
    closeButton.hide();
    error.hide();
    spinner.show();
    titleStart.show();
    titleEnd.hide();

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

    titleStart.hide();
    titleEnd.show();
    closeButton.show(200);
});
