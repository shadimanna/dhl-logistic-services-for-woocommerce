<?php

if (!defined('ABSPATH')) { exit; }

if (!function_exists('dhlpwc_esc_template')) :

function dhlpwc_esc_template() {
    return call_user_func_array(
        array(
            forward_static_call_array(array('DHLPWC_Model_Service_Escape_Output', 'instance'), array()),
            'esc_template'
        ),
        func_get_args()
    );
}

endif;
