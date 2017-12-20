<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Order_Meta')) :

/**
 * Handles the more complex usage of meta data
 */
class DHLPWC_Model_Logic_Order_Meta extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_from_stack($meta_key, $order_id, $stack_id)
    {
        $stack = $this->get_stack($meta_key, $order_id);
        if (!is_array($stack) || !array_key_exists($stack_id, $stack)) {
            return false;
        }
        return $stack[$stack_id];
    }

    public function add_to_stack($meta_key, $order_id, $stack_id, $meta_object)
    {
        $stack = $this->get_stack($meta_key, $order_id);
        if (!is_subclass_of($meta_object, 'DHLPWC_Model_Meta_Abstract')) {
            return false;
        }
        /** @var DHLPWC_Model_Meta_Abstract $meta_object */
        $stack[$stack_id] = $meta_object->to_array();
        update_post_meta($order_id, $meta_key, $stack);
        return $stack_id;
    }

    public function update_in_stack($meta_key, $order_id, $stack_id, $meta_object)
    {
        $stack = $this->get_stack($meta_key, $order_id);
        if (is_array($stack) && array_key_exists($stack_id, $stack) && is_subclass_of($meta_object, 'DHLPWC_Model_Meta_Abstract')) {
            $class = get_class($meta_object);
            /** @var DHLPWC_Model_Meta_Abstract $meta_object */
            $merged_data = array_replace_recursive($stack[$stack_id], $meta_object->to_array());
            /** @var DHLPWC_Model_Meta_Abstract $merged_object */
            $merged_object = new $class($merged_data);
            $stack[$stack_id] = $merged_object->to_array();

            update_post_meta($order_id, $meta_key, $stack);
            return $merged_object;
        }
        return false;
    }

    public function remove_from_stack($meta_key, $order_id, $stack_id)
    {
        $stack = $this->get_stack($meta_key, $order_id);
        if (!is_array($stack) || !array_key_exists($stack_id, $stack)) {
            return false;
        }

        $object = $stack[$stack_id];
        $stack[$stack_id] = null;
        unset($stack[$stack_id]);

        update_post_meta($order_id, $meta_key, $stack);

        return $object;
    }

    public function get_stack($meta_key, $order_id)
    {
        $stack = get_post_meta($order_id, $meta_key, true);
        if (!is_array($stack)) {
            $stack = array();
        }
        return $stack;
    }

}

endif;
