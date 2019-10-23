jQuery(document).ready(function ($) {
    $(document.body).on('change', '#shipping_product_data #dhlpwc_enable_method_limit', function (e) {
        if(this.checked){
            $('#shipping_product_data #dhlpwc_selected_method_limit').prop('disabled', false);
        }else{
            $('#shipping_product_data #dhlpwc_selected_method_limit').prop('disabled', true);
        }
    });

    $('#shipping_product_data #dhlpwc_enable_method_limit').trigger('change');
});
