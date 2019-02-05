<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Template')) :

class DHLPWC_Template
{

    protected $template_code;
    protected $template;

    public function __construct($template)
    {
        $this->template_code = $this->get_template_code_from_name($template);
        $root_path = trailingslashit(DHLPWC_PLUGIN_DIR . implode(DIRECTORY_SEPARATOR, array('includes', 'view')));
        $template_path = $this->get_path_from_name($template);
        $this->set_template($root_path . $template_path);
    }

    public function render($context = null, $echo = true)
    {
        if (is_array($context) && empty($context)) {
            $context = null;
        }

        if (!is_null($context) && !$this->is_assoc($context)) {
            throw new InvalidArgumentException('Context must be either null or an associative array', 1);
        }

        if (!is_null($context) && is_array(($context))) {
            foreach ($context as $key => $value) {
                ${$key} = $value;
            }
        }

        ob_start();
        require($this->template);
        $output = ob_get_clean();

        // Allow developers to overwrite output
        $output = apply_filters('dhlpwc_render_template_' . $this->template_code, $output);

        if ($echo) {
            echo $output;
        }

        return $output;
    }

    public function get_template()
    {
        return $this->template;
    }

    protected function set_template($template_file_path)
    {
        // Allow developers to use other templates
        $template_file_path = apply_filters('dhlpwc_set_template_' . $this->template_code, $template_file_path);

        if (!is_string($template_file_path) && !file_exists($template_file_path)) {
            throw new InvalidArgumentException(sprintf("Template %s doesn't exist.", $template_file_path), 1);

        }
        $this->template = $template_file_path;
    }

    protected function get_path_from_name($name)
    {
        return strtolower(str_replace('.', DIRECTORY_SEPARATOR, $name )) . '.php';
    }

    protected function get_template_code_from_name($name)
    {
        return strtolower(str_replace('.', '_', $name ));
    }

    protected function is_assoc($array)
    {
        foreach (array_keys($array) as $k => $v) {
            if ($k !== $v) {
                return true;
            }
        }
        return false;
    }

}

endif;
