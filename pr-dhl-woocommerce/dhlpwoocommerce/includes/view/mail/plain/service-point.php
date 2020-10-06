<?php

/**
 * @var string                        $label
 * @var DHLPWC_Model_API_Data_Address $address
 */

if (!defined('ABSPATH')) { exit; }

echo $label . ':' . $name . "\n";
echo $address->street . "\n";
echo $address->postal_code . "\n";
echo $address->country_code . "\n\n";
