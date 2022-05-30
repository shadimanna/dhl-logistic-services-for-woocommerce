

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
    
    //jQuery( document ).on( 'nextWizard', pr_dhl_wizard_next_wizard );

    var next_buttons = jQuery(".button-next");
    next_buttons.on( 'click', pr_dhl_wizard_next_button );
});

function pr_dhl_wizard_next_button( evt ) {
    evt.preventDefault();
    pr_dhl_wizard_save_fields();
}

function pr_dhl_wizard_save_fields() {
    var active_wizard = jQuery( '.wizard-content .wizard-step.active' );
    var active_fields = active_wizard.find( '.wizard-dhl-field' );

    if ( 1 > active_fields.length ) {
        jQuery('.wizard-btn.btn.next').click();
        return;
    }

    var all_fields = dhl_wizard_obj.all_fields;
    var field_values = [];

    active_fields.each( function( idx ) {
        var field       = jQuery( this );
        var field_name  = field.prop( 'name' );
        
        if ( all_fields.indexOf( field_name ) > -1 ) {
            var field_value = field.val();

            field_values.push( {
                'name': field_name,
                'value': field_value
            } );
        }
    } );

    console.log( 'tes field values' );
    console.log( field_values );

    if ( field_values.length > 0 ) {
        jQuery.ajax({
            method: "POST",
            url: dhl_wizard_obj.ajaxurl,
            data: {
                'action': 'save_wizard_fields',
                'nonce': dhl_wizard_obj.nonce,
                'fields': field_values
            },
            beforeSend: function() {
                console.log( 'before send' );
            },
            success: function( resp ) {
                console.log( 'success' );
                if ( resp.success ) {
                    jQuery('.wizard-btn.btn.next').click();
                } else {
                    alert( resp.errortext );
                }
            },
            error: function( data ) {
                console.log( data );
                console.log( 'error' );
            }
        });
    } else {
        console.log( 'no field values' );
        console.log( field_values );
        jQuery('.wizard-btn.btn.next').click();
    }
}