<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Meta_Order_Label')) :

class DHLPWC_Model_Meta_Order_Label extends DHLPWC_Model_Meta_Abstract
{

    protected $class_map = array(
        'pdf' => 'DHLPWC_Model_Meta_Order_Label_PDF',
    );

    public $label_id;
    public $label_type;
    public $label_size;
    public $tracker_code;
    public $routing_code;
    public $order_reference;
    public $is_return;
	public $request;

    /** @var DHLPWC_Model_Meta_Order_Label_PDF $pdf */
    public $pdf;

}

endif;
