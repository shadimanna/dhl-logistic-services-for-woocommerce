

jQuery( document ).ready( function(){
    var wizard_overlay = jQuery( '.pr-dhl-wc-wizard-overlay' );
    
    if ( 1 > wizard_overlay.length ) {
        return;
    }

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

    wizard_overlay.find( '#wizard_dhl_participation_V01PAK' ).on( 'change', function( evt ){
        var participant_value = jQuery( this ).val();
        wizard_overlay.find( '.wizard-dhl-field.participation-field' ).val( participant_value );
    } );

    jQuery( document ).on( 'nextWizard', pr_dhl_wizard_update_fields );

    jQuery( document ).on( 'submitWizard', function (e) {
        jQuery( 'p.submit button.woocommerce-save-button' ).click();
    });

    var close_button = jQuery( '.pr-dhl-wc-skip-wizard' );
    close_button.on( 'click', function( evt ) {
        evt.preventDefault();

        wizard_overlay.addClass( 'hidden' );
    } );

    var next_buttons = jQuery(".button-next");
    next_buttons.on( 'click', pr_dhl_wizard_next_button );

    var finish_button = jQuery(".button-finish");
    finish_button.on( 'click', pr_dhl_wizard_finish_button );
});

function pr_dhl_wizard_next_button( evt ) {
    evt.preventDefault();

    pr_dhl_wizard_update_fields();

    jQuery('.wizard-btn.btn.next').click();
}

function pr_dhl_wizard_finish_button( evt ) {
    evt.preventDefault();
    jQuery('.wizard-btn.btn.finish').click();
    jQuery( 'p.submit button.woocommerce-save-button' ).click();
}

function pr_dhl_wizard_update_fields() {

    var active_wizard = jQuery( '.wizard-content .wizard-step.active' );
    var active_fields = active_wizard.find( '.wizard-dhl-field' );

    var field_values = [];

    active_fields.each( function( idx ) {
        var field       = jQuery( this );
        var field_name  = field.prop( 'name' );
        var field_value = field.val();

        field_values.push( {
            'name': field_name,
            'value': field_value
        } );
    } );

    for( var idx = 0; idx < field_values.length; idx++ ) {
        var field_name = field_values[ idx ].name;
        var field_value = field_values[ idx ].value;
        var field = jQuery( '#woocommerce_pr_dhl_paket_' + field_name );

        if ( 0 < field.length ) {
            field.val( field_value );
        }
    }
}