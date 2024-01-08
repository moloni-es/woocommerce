if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Login = (function($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        var loginBtn = $('#login_button');
        var clientSecret = $('#client_secret');
        var developerId = $('#developer_id');

        if (!loginBtn.length || !clientSecret.length || !developerId.length) {
            return;
        }

        clientSecret.add(developerId).on('keyup', function () {
            if (clientSecret.val() === '' || developerId.val() === '') {
                loginBtn.prop('disabled', true);
            } else {
                loginBtn.removeAttr("disabled");
            }
        });
    }

    return {
        init: init,
    }
}(jQuery));
