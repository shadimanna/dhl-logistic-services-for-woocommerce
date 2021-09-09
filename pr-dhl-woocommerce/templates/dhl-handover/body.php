<?php
defined( 'ABSPATH' ) or exit;

$logo_url = PR_DHL_PLUGIN_DIR_URL . '/assets/img/dhl-official.png';
$shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
$weight_units = get_option( 'woocommerce_weight_unit' );

$shipping_services = '';
foreach( $dhl_products as $dhl_product ) {
  $shipping_services .= $dhl_product . '<br/>';
}

$t = getdate();
$date = $t["mday"] . "-" . $t["month"] . "-" . $t["year"] . " " . $t["hours"] . ":" . $t["minutes"] . ":" . $t["seconds"];
?>

<div class="container">

  <a class="print-button" href="#" onclick="window.print()"><?php echo __( 'Print' ); ?></a>

  <header class="main-header">
    <img src="<?php echo $logo_url; ?>" alt="DHL logo" class="logo">
    <div class="document-title">
      <h1><?php echo __("Handover Note", 'dhl-for-woocommerce'); ?></h1>
    </div>
    <div class="header-barcode">
      <p><?php echo $handover_id; ?></p>
      <?php echo PR_DHL()->generate_barcode( $handover_id ); ?>
    </div>
  </header>

  <section>
    <div class="section-header">
      <span class="num">1</span> <?php echo __("Pick-up Account Details", 'dhl-for-woocommerce'); ?>
    </div>
    <div class="section-body section-1">

      <div class="sub-section">
        <div class="name"><?php echo __("Pick-up Name", 'dhl-for-woocommerce'); ?></div>
        <div class="box"><?php echo $shipping_dhl_settings['dhl_pickup_name']; ?></div>
      </div>

      <div class="sub-section">
        <div class="name"><?php echo __("Account No", 'dhl-for-woocommerce'); ?></div>
        <div>
          <p><?php echo $shipping_dhl_settings['dhl_pickup']; ?></p>
           <?php echo PR_DHL()->generate_barcode( $shipping_dhl_settings['dhl_pickup'] ); ?>
        </div>
      </div>

    </div>
  </section>

  <section>
    <div class="section-header">
      <span class="num">2</span> <?php echo __("Shipping Service(s)", 'dhl-for-woocommerce'); ?>
    </div>
    <div class="section-body section-2">
      <div class="name"><?php echo __("Shipping Service(s)"); ?></div>
      <div class="box"><?php echo $shipping_services; ?></div>
    </div>
  </section>

  <section>
    <div class="section-header">
      <span class="num">3</span> <?php echo __("Details", 'dhl-for-woocommerce'); ?>
    </div>
    <div class="section-body section-3">

      <div class="row row-1">
        <div class="row-title"><?php echo __("Total", 'dhl-for-woocommerce'); ?></div>
        <div class="item">
          <p><?php echo __("No. of items", 'dhl-for-woocommerce'); ?></p>
          <div class="box"><?php echo $items_qty; ?></div>
        </div>
        <div class="item">
          <p><?php echo sprintf( __( 'Weight (%s)', 'dhl-for-woocommerce' ), $weight_units); ?></p>
          <div class="box"><?php echo $total_weight; ?></div>
        </div>
        <div class="item">
          <p><?php echo __("No. of Receptacles", 'dhl-for-woocommerce'); ?></p>
          <div class="box"></div>
        </div>
      </div>

      <div class="row row-2">
        <div class="row-title"><?php echo __("Handover info", 'dhl-for-woocommerce'); ?></div>
        <div>
          <div class="handover-option">
            <div class="circle <?php echo $shipping_dhl_settings['dhl_handover_type'] == 'dropoff' ? 'active' : ''; ?>"></div> <?php echo __("Drop-Off"); ?>
          </div>
          <div class="handover-option">
            <div class="circle <?php echo $shipping_dhl_settings['dhl_handover_type'] == 'pickup' ? 'active' : ''; ?>"></div> <?php echo __("Pick-Up"); ?>
          </div>
        </div>
        <div class="dist-item">
          <p><?php echo __("DHL Distribution centre"); ?></p>
          <div class="box"><?php echo $shipping_dhl_settings['dhl_distribution']; ?></div>
        </div>
      </div>

      <div class="row row-3">
        <div class="row-title"><?php echo __("Remarks/VAS", 'dhl-for-woocommerce'); ?></div>
        <div class="underline-box"></div>
      </div>

    </div>
  </section>

  <section>
    <div class="section-header">
      <span class="num">4</span> <?php echo __("Signature", 'dhl-for-woocommerce'); ?>
    </div>
    <div class="section-body section-4">
      <p><?php echo __("I declare the contents of the shipment under this Handover Note does not contain any prohibited or hazardous goods. The General Terms and Conditions of DHL eCommerce shall apply on the services provided by DHL eCommerce.", 'dhl-for-woocommerce'); ?></p>
      <div class="sub-section">
        <div><?php echo __("Signature", 'dhl-for-woocommerce'); ?></div>
        <div><?php //echo __("UBI Logistics (China)", 'dhl-for-woocommerce'); ?></div>
        <div><?php echo __("Date", 'dhl-for-woocommerce'); ?> <?php echo $date; ?></div>
      </div>
    </div>
  </section>

  <a class="print-button" href="#" onclick="window.print()"><?php echo __( 'Print' ); ?></a>

</div>
