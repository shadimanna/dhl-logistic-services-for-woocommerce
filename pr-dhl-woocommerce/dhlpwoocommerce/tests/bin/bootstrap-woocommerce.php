<?php
/**
 * PHPUnit bootstrap woocommerce file
 *
 * @package Dhlpwoocommerce
 */

$wp_install_plugin_format = 'wp plugin install %s --activate';

if (posix_getuid() === 0) {
    $sudo_format = 'sudo -u www-data -- %s';
    $wp_install_plugin_format = sprintf($sudo_format, $wp_install_plugin_format);

}

$command = sprintf($wp_install_plugin_format, 'woocommerce');

exec($command);

