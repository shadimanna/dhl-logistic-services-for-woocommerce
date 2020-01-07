<?php

use PR_DHL\lib\PDFMerger\PDFMerger;

if (!defined('ABSPATH')) { 
    exit; 
}

if( !class_exists( 'PR_DHL_Libraryloader' ) ){

    class PR_DHL_Libraryloader{

        const CLASS_PDF_MERGER = 'pdf_merger';

        private static $instances = array();

        protected $include_path = null;
        protected $file_path = null;
        protected $loaded = array();

        /**
         * Constructor.
         */
        public function __construct(){
            // Set paths
            $this->include_path     = PR_DHL_PLUGIN_DIR_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
            $this->file_path        = $this->include_path . 'PDFMerger' . DIRECTORY_SEPARATOR . 'PDFMerger.php';
            $this->file_path        = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $this->file_path);
        }

        /**
         * Returns a singleton instance of the called class
         * @return static
         */
        public static function instance(){

            $class = get_called_class();
            if (!isset(self::$instances[$class])) {
                self::$instances[$class] = new static();
            }
            return self::$instances[$class];
            
        }

        public function get_pdf_merger()
        {
            if (!class_exists('PR_DHL\lib\PDFMerger\PDFMerger')) {
                $loaded = $this->include_file($this->file_path);

                if (!$loaded) {
                    return null;
                }
                $this->loaded[] = self::CLASS_PDF_MERGER;
            }

            // Something very unexpected happened, return
            if (!class_exists('PR_DHL\lib\PDFMerger\PDFMerger')) {
                return null;
            }

            return new PDFMerger();
        }

        /**
         * Include a file if it exists.
         *
         * @param $path
         * @return bool
         */
        protected function include_file($path){
            
            if (file_exists($path)) {
                // Supress errors of third party libraries
                $status = @include_once $path;
                if ($status !== 1) {
                    return false;
                }
                return true;
            }
            return false;
        }
    }
}