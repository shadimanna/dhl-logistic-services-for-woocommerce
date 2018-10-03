 // shorthand no-conflict safe document-ready function
jQuery(function($) {
  $( document ).on( 'click', '#dhl-optin-notice .notice-dismiss', function () {
      // is being dismissed and send it via AJAX
      $.ajax( dhl_dismiss_notice.ajax_url,
        {
          type: 'POST',
          data: {
            action: 'dhl_dismissed_notice_handler',
            security: dhl_dismiss_notice.security,
          }
        } );
    } );
});