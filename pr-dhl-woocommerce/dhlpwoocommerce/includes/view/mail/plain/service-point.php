<?php

/**
 * @var string                        $label
 * @var DHLPWC_Model_API_Data_Address $address
 * @var string                        $name
 */

if (!defined('ABSPATH')) { exit; }

echo esc_html($label) . ':' . esc_html($name) . "\n";
echo esc_html($address->street) . "\n";
echo esc_html($address->postal_code) . "\n";
echo esc_html($address->country_code) . "\n\n";
