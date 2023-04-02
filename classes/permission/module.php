<?php

/**
 *
 * @author  2021 Marc Burchart <marc.burchart@tu-dortmund.de> , Kooperative Systeme, FernUniversität Hagen
 * 
 */

namespace mod_hypercast\permission;

defined('MOODLE_INTERNAL') || die();

class module extends context {
    function __construct($userid, $cmid){
        $context = \context_module::instance($cmid);
        parent::__construct($userid, $context);
    }
}

?>
