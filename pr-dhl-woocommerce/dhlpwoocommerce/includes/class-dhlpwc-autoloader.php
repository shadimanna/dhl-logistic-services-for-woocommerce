<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Autoloader')) :

/**
 * Automatically loads classes for the DHLPWC if not found.
 * Supports hierarchic folder structures and files, based on convention
 *
 * Classes are generally written in StudlyCaps, but words are split with underscores.
 * However, what the caps are aren't really necessary, just for readability and consistency.
 *
 * Classes begin with 'DHLPWC_', the "namespace" of this plugin.
 *
 * This autoloader will automatically search for files containing the class code.
 *
 * Example of how the plugin searches:
 *
 * DHLPWC_Controller_Test_Foo_Bar
 *
 * The autoloader will look for the file class-dhlpwc-controller-test-foo-bar.php
 * Each part of the name will be considered a folder, as long as the folder exists.
 *
 * It will try to find the file in:
 * - includes/controller/test/foo/
 * However, if there is no 'foo' folder, it assumed the location would be in:
 * - includes/controller/test/
 *
 * Etc.
 *
 * If a class ends with '_Abstract' or '_Interface', the autoloader will search for a file starting with
 * 'abstract-' or 'interface-' respectively, instead of 'class-'.
 */
class DHLPWC_Autoloader
{

    const CLASS_PREFIX = 'dhlpwc_';

    protected $include_path = '';
    protected $sub_folders = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set paths
        $this->include_path = plugin_dir_path(__FILE__);
        $this->sub_folders = $this->get_all_sub_folders($this->include_path);

        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }

        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Load the correct file.
     * @param $class
     */
    public function autoload($class)
    {
        if (!$this->check_valid_class_name($class)) {
            return;
        }

        $file = $this->get_file_name_from_class($class);
        $this->include_file($this->include_path.$this->get_sub_folder($class).$file);
    }

    /**
     * Scans all sub folders
     *
     * @param $path
     * @param null $root_path
     * @return array
     */
    protected function get_all_sub_folders($path, $root_path = null)
    {
        if (!$root_path) {
            $root_path = $path;
        }
        $folders = glob($path.'*', GLOB_ONLYDIR | GLOB_MARK);
        $folder_names = array();
        foreach($folders as $folder) {
            if (substr($folder, 0, strlen($path)) == $path) {
                $relative_path = substr($folder, strlen($root_path));
                $folder_names[] = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relative_path);
                $folder_names = array_merge($folder_names, $this->get_all_sub_folders($folder, $root_path));
            }
        }
        return $folder_names;
    }

    /**
     * Find the deepest possible path based on the class name
     *
     * @param $class
     * @return mixed|string
     */
    protected function get_sub_folder($class)
    {
        $search = '';
        foreach($this->sub_folders as $sub_folder) {
            $namespace = str_replace(DIRECTORY_SEPARATOR, '_', $sub_folder);
            if (strpos(strtolower($class), self::CLASS_PREFIX.strtolower($namespace)) === 0) {
                // Use the deepest path possible
                if (strlen($sub_folder) > $search) {
                    $search = $sub_folder;
                }
            }
        }
        return $search;
    }

    /**
     * Checks if the class name is something that needs to be checked for by this plugin
     *
     * @param $class
     * @return bool
     */
    protected function check_valid_class_name($class)
    {
        if (strpos(strtolower($class), self::CLASS_PREFIX) !== 0) {
            return false;
        }
        return true;
    }

    /**
     * Convert a class string to a filename string.
     *
     * @param $class
     * @return string
     */
    protected function get_file_name_from_class($class)
    {
        if (preg_match('/_Abstract$/', $class)) {
            return 'abstract-' . str_replace( '_', '-', strtolower($class) ) . '.php';
        }

        if (preg_match('/_Interface$/', $class)) {
            return 'interface-' . str_replace( '_', '-', strtolower($class) ) . '.php';
        }

        return 'class-' . str_replace( '_', '-', strtolower($class) ) . '.php';
    }

    /**
     * Include a file if it exists.
     *
     * @param $path
     * @return bool
     */
    protected function include_file($path)
    {
        if ($path && is_readable($path)) {
            include_once($path);
            return true;
        }
        return false;
    }

}

return new DHLPWC_Autoloader();

endif;
