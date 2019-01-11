<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Core_Arraymap_Abstract')) :

/**
 * This abstract helps with converting data to classed objects
 * to make it easier to work with data by predefining how a structure
 * looks like.
 *
 * It also offers hooks to format key strings when exported as an array
 */
abstract class DHLPWC_Model_Core_Arraymap_Abstract
{

    protected $class_map = array();
    protected $array_class_map = array();
    protected $rename_map = array();
    protected $ignore_null_map = array();

    public function __construct($automap = array())
    {
        $this->set_array($automap);
    }

    public function set_array($array = array())
    {
        if (is_array($array) && !empty($array)) {
            $array = $this->rename_map($array);

            $me = $this;
            $data = function() use ($me) {
                return get_object_vars($me);
            };

            foreach ($data() as $key => $value) {
                $store_key = $this->store_key($key);
                if (array_key_exists($key, $array) && $store_key != 'class_map' && $store_key != 'rename_map' && $store_key != 'array_class_map' && $store_key != 'ignore_null_map') {
                    if (array_key_exists($store_key, $this->class_map) && !$array[$key] instanceof DHLPWC_Model_Core_Arraymap_Abstract) {
                        $this->$store_key = new $this->class_map[$store_key]($array[$key]);
                    } else if (array_key_exists($store_key, $this->array_class_map) && is_array($array[$key])) {
                        $this->$store_key = array();
                        foreach($array[$key] as $entry) {
                            if (!$entry instanceof DHLPWC_Model_Core_Arraymap_Abstract) {
                                $this->{$store_key}[] = new $this->array_class_map[$store_key]($entry);
                            } else {
                                $this->{$store_key}[] = $entry;
                            }
                        }
                    } else {
                        $this->$store_key = $array[$key];
                    }
                } else if (array_key_exists($this->format_key($key), $array) && $store_key != 'class_map' && $store_key != 'rename_map' && $store_key != 'array_class_map' && $store_key != 'ignore_null_map') {
                    if (array_key_exists($store_key, $this->class_map) && !$array[$this->format_key($key)] instanceof DHLPWC_Model_Core_Arraymap_Abstract) {
                        $this->$store_key = new $this->class_map[$store_key]($array[$this->format_key($key)]);
                    } else if (array_key_exists($store_key, $this->array_class_map) && is_array($array[$this->format_key($key)])) {
                        $this->$store_key = array();
                        foreach($array[$this->format_key($key)] as $entry) {
                            if (!$entry instanceof DHLPWC_Model_Core_Arraymap_Abstract) {
                                $this->{$store_key}[] = new $this->array_class_map[$store_key]($entry);
                            } else {
                                $this->{$store_key}[] = $entry;
                            }
                        }
                    } else {
                        $this->$store_key = $array[$this->format_key($key)];
                    }
                }
            }
        }
    }

    protected function rename_map($array = array())
    {
        if (is_array($array) && !empty($array)) {
            $me = $this;
            $data = function() use ($me) {
                return get_object_vars($me);
            };

            foreach($this->rename_map as $key => $new_key) {
                // Continue if the new key doesn't exist in the data array
                if (!array_key_exists($new_key, $array) && !array_key_exists($this->format_key($new_key), $array)) {
                    // Check if the new key is allowed
                    if (array_key_exists($new_key, $data())) {
                        // Save normally if the old key is found in regular format
                        if (array_key_exists($key, $array)) {
                            $array[$new_key] = $array[$key];
                            $array[$key] = null;
                            unset($array[$key]);

                        // Save as formatted key if the old key is found in formatted state
                        } else if (array_key_exists($this->format_key($key), $array)) {
                            $array[$this->format_key($new_key)] = $array[$this->format_key($key)];
                            $array[$this->format_key($key)] = null;
                            unset($array[$this->format_key($key)]);
                        }
                    }
                }
            }
        }

        return $array;

    }

    public function to_array()
    {
        $me = $this;
        $data = function() use ($me) {
            return get_object_vars($me);
        };

        $formatted_data = array();
        foreach($data() as $key => $value) {
            if ($key != 'class_map' && $key != 'rename_map' && $key != 'array_class_map' && $key != 'ignore_null_map') {
                if ($value instanceof DHLPWC_Model_Core_Arraymap_Abstract) {
                    $formatted_data[$this->format_key($key)] = $value->to_array();
                } else if (array_key_exists($key, $this->array_class_map) && is_array($value)) {
                    $array_values = array();
                    foreach($value as $array_value) {
                        if ($array_value instanceof DHLPWC_Model_Core_Arraymap_Abstract) {
                            $array_values[] = $array_value->to_array();
                        } else {
                            /** @var DHLPWC_Model_Core_Arraymap_Abstract $class */
                            $class = new $this->array_class_map[$key]($value);
                            $array_values[] = $class->to_array();
                        }
                    }
                    $formatted_data[$this->format_key($key)] = $array_values;
                } else {
                    if ($value !== null || !in_array($key, $this->ignore_null_map)) {
                        $formatted_data[$this->format_key($key)] = $value;
                    }
                }
            }
        }

        return $formatted_data;
    }

    protected function store_key($key)
    {
        return $key;
    }

    protected function format_key($key)
    {
        return $key;
    }

}

endif;
