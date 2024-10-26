<?php

class FWF_Plugin {

    public function __construct() {
        
    }

    public static function activate() {
        set_transient( 'fwf_activation_notice', true, 5 );
    }

    public function set_options() {

    }

  
    public function wc_requirement_notice() {

    }
}