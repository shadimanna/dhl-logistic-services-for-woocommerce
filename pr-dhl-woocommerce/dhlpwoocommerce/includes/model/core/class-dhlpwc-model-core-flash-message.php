<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Core_Flash_Message')) :

class DHLPWC_Model_Core_Flash_Message extends DHLPWC_Model_Core_Singleton_Abstract
{

    const GROUP_DEFAULT = 'default';

    protected $messages = array();

    public function add_notice($message, $group = null)
    {
        $group = $group ?: self::GROUP_DEFAULT;
        $this->add_message($message, 'notice', $group);
    }

    public function add_error($message, $group = null)
    {
        $group = $group ?: self::GROUP_DEFAULT;
        $this->add_message($message, 'error', $group);
    }

    public function add_warning($message, $group = null)
    {
        $group = $group ?: self::GROUP_DEFAULT;
        $this->add_message($message, 'warning', $group);
    }

    protected function add_message($message, $type, $group)
    {
        $this->messages = array_merge_recursive($this->messages, array(
            $group => array(
                $type => array(
                    $message
                )
            )
        ));
    }

    public function get_notices($group = null)
    {
        $group = $group ?: self::GROUP_DEFAULT;
        return $this->get_messages('notice', $group);
    }

    public function get_errors($group = null)
    {
        $group = $group ?: self::GROUP_DEFAULT;
        return $this->get_messages('error', $group);
    }

    public function get_warnings($group = null)
    {
        $group = $group ?: self::GROUP_DEFAULT;
        return $this->get_messages('warning', $group);
    }


    protected function get_messages($type, $group)
    {
        if (!isset($this->messages[$group])) {
            return null;
        } else if (!isset($this->messages[$group][$type])) {
            return null;
        } else if (empty($this->messages[$group][$type])) {
            return null;
        }

        return $this->messages[$group][$type];
    }

}

endif;
