<?php
defined( 'ABSPATH' ) or exit;

$packstation_img = PR_DHL_PLUGIN_DIR_URL . '/assets/img/packstation.png';
$parcelshop_img = PR_DHL_PLUGIN_DIR_URL . '/assets/img/parcelshop.png';
$post_office_img = PR_DHL_PLUGIN_DIR_URL . '/assets/img/post_office.png';

try {
  $shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
  $dhl_obj = PR_DHL()->get_dhl_factory();
} catch (Exception $e) {
    return;
}

?>

<a id="dhl_parcel_finder" class="button" href="#dhl_parcel_finder_form">Parcel Finder</a>
<div style="display:none">
  <div id="dhl_parcel_finder_form">
    <!-- Create form and call via AJAX parcel finder API -->
  </div>
</div>