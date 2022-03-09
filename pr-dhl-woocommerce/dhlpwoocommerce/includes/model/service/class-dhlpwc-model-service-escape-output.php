<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Escape_Output')) :

class DHLPWC_Model_Service_Escape_Output extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function esc_template($var, $debug = false)
    {
        if ($debug) {
            $this->debug($var);
        }
        return wp_kses($var, $this->template());
    }

    protected function template()
    {
        return array(
            'a' => array(
                'class' => array(),
                'data-post-id' => array(),
                'data-tip' => array(),
                'href' => array(),
                'label-id' => array(),
                'target' => array(),
            ),
            'b' => array(),
            'br' => array(),
            'button' => array(
                'class' => array(),
                'id' => array(),
                'type' => array(),
                'value' => array(),
            ),
            'div' => array(
                'class' => array(),
                'data-key' => array(),
                'data-limit' => array(),
                'data-option-input' => array(),
                'data-query' => array(),
                'id' => array(),
                'style' => array(),
            ),
            'h3' => array(),
            'hr' => array(),
            'i' => array(),
            'img' => array(
                'src' => array(),
            ),
            'input' => array(
                'checked' => array(),
                'class' => array(),
                'data-exclusions' => array(),
                'id' => array(),
                'name' => array(),
                'placeholder' => array(),
                'type' => array(),
                'value' => array(),
            ),
            'label' => array(
                'class' => array(),
                'for' => array(),
                'id' => array(),
            ),
            'p' => array(),
            'small' => array(),
            'strong' => array(),
        );
    }

    protected function search_tags($input)
    {
        $dom = new DOMDocument;
        $dom->loadHTML($input);
        $allElements = $dom->getElementsByTagName('*');
        return $this->extract_attributes($allElements);
    }

    protected function check_missing_tags($searched)
    {
        $missing = array();
        $template_tags = $this->template();
        foreach($searched as $element => $attributes) {
            if (!array_key_exists($element, $template_tags)) {
                if (!isset($missing[$element])) {
                    $missing[$element] = array();
                }
            }
            foreach($attributes as $attribute => $array) {
                if (!array_key_exists($attribute, $template_tags[$element])) {
                    $missing[$element][$attribute] = array();
                }
            }
            if (isset($missing[$element])) {
                ksort($missing[$element]);
            }
        }
        ksort($missing);
        return $missing;
    }

    protected function extract_attributes($input)
    {
        $inventory = array();
        foreach($input as $element) {
            if (!array_key_exists($element->tagName, $inventory)) {
                $inventory[$element->tagName] = array();
            }
            if ($element->hasAttributes()) {
                foreach ($element->attributes as $attribute) {
                    if (!in_array($attribute->nodeName, $inventory[$element->tagName])) {
                        $inventory[$element->tagName][$attribute->nodeName] = array();
                    }
                }
                ksort($inventory[$element->tagName]);
            }
        }
        unset($inventory['html']);
        unset($inventory['body']);
        ksort($inventory);
        return $inventory;
    }

    protected function generate_allowed($inventory)
    {
        ksort($inventory);
        $output = '';
        foreach($inventory as $element => $attributes) {
            $output .= "'$element' => array(";
            if (!empty($attributes)) {
                ksort($attributes);
                $output .= "\n";
                foreach($attributes as $attribute => $array) {
                    $output .= "'$attribute' => array(),\n";
                }
            }
            $output .= "),\n";
        }
        return $output;
    }

    protected function debug($var)
    {
        if (!empty($this->check_missing_tags($this->search_tags($var)))) {
            /** Example missing template attributes report
            $missing_tags = $this->generate_allowed($this->check_missing_tags($this->search_tags($var)));
            $new_template_tags = $this->generate_allowed(array_merge_recursive($this->template(), $this->search_tags($var)));
            // */
        }
    }

}

endif;
