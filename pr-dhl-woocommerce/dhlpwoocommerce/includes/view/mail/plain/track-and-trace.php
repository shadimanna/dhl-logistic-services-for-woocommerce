<?php

if (!defined('ABSPATH')) { exit; }

if (isset($tracking_codesets) && is_array($tracking_codesets) && !empty($tracking_codesets)) {

    echo strtoupper( __( 'Tracking', 'dhlpwc' ) ) . "\n\n";
    echo esc_html($text)."\n\n";

    foreach ($tracking_codesets as $tracking_codeset) {
        echo esc_attr($tracking_codeset['code'])." - ".esc_url($tracking_codeset['url'])."\n";
    }

    echo '=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=';
    echo "\n\n";

}
