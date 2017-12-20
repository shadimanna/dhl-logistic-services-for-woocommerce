<?php

// Start up the WP+WC testing environment.

/* standard install in wordpress, same level */
$wcTestPathInWP = dirname(dirname(__DIR__)) . '/woocommerce/tests';
$wcTestPathInVendor = dirname(__DIR__) . '/vendor/woocommerce/tests';

clearstatcache();

if (is_dir($wcTestPathInWP)) {
    require $wcTestPathInWP . '/bootstrap.php';
} elseif (is_dir($wcTestPathInVendor)) {
    require $wcTestPathInVendor . '/bootstrap.php';
} else {
    echo 'Unable to locate woocommerce test bootstrap on regular locations; aborting' . PHP_EOL;
    echo 'regular locations: ' . PHP_EOL;
    echo '$wcTestPathInWP: ' . $wcTestPathInWP . PHP_EOL;
    echo '$wcTestPathInVendor: ' . $wcTestPathInVendor . PHP_EOL;
    exit(2);
}
