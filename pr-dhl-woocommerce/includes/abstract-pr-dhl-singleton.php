<?php

if( !defined('ABSPATH' ) ){
    exit;
}

if( !class_exists( 'PR_DHL_Singleton' ) ){

    abstract class PR_DHL_Singleton{

        private static $instances = array();

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
    }
}