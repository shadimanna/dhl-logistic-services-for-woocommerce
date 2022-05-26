

jQuery( document ).ready( function(){
    let args = {
        "wz_nav_style": "dots", // dots, tabs, progress
        "wz_ori" : "horizontal",
        "buttons": true,
        "navigation": 'all', // buttons, nav, all
        "next": '<span class="dashicons dashicons-arrow-right-alt2"></span>',
        "prev": '<span class="dashicons dashicons-arrow-left-alt2"></span>',
    };
    
    const wizard = new Wizard(args);
    
    wizard.init();
    
    jQuery( document ).on( 'nextWizard', pr_dhl_wizard_next_wizard );

    var next_buttons = jQuery(".button-next");
    next_buttons.on( 'click', pr_dhl_wizard_next_button );
});

function pr_dhl_wizard_next_button( evt ) {
    evt.preventDefault();
    jQuery('.wizard-btn.btn.next').click();
}

function pr_dhl_wizard_next_wizard( evt ) {
    jQuery.ajax({
        method: "POST",
        url: dhl_wizard_obj.ajaxurl,
        data: {
            'action': 'save_wizard_fields',
            'nonce': dhl_wizard_obj.nonce,
        },
        beforeSend: function() {
            console.log( dhl_wizard_obj.ajaxurl );
            console.log( dhl_wizard_obj.nonce );
            console.log( 'before send' );
        },
        success: function( data ) {
            console.log( data );
            console.log( 'success' );
        },
        error: function( data ) {
            console.log( data );
            console.log( 'error' );
        }
    });
}