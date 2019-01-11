jQuery(document).ready(function($) {

    $.getScript(dhlpwc_usabilla_object.usabilla_js).done(function() {
        if (dhlpwc_usabilla_object.language !== 'nl') {
            window.usabilla_live("setForm", "Feedback_EN");
        }
        // Set triggers
        $(document.body).on('dhlpwc:settings_clicked', function (e, identifier) {
            if (identifier === 'woocommerce_dhlpwc_feedback_settings') {
                window.usabilla_live("click");
            }
        });
    });

});
