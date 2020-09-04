jQuery(document).ready(function ($) {
  if (dhlpwc_mopinion_object.language !== 'nl') {
    window.dhlparcel_shipping_mopinion_language = dhlpwc_mopinion_object.language;
    window.dhlparcel_shipping_mopinion_framework = dhlpwc_mopinion_object.framework;
  }
  $.getScript(dhlpwc_mopinion_object.mopinion_js).done(function () {
    // Set triggers
    $(document.body).on('dhlpwc:settings_clicked', function (e, identifier) {
      if (identifier === 'woocommerce_dhlpwc_feedback_settings') {
        srv.openModal(true, dhlpwc_mopinion_object.form_key);
      }
    });
  });
});
