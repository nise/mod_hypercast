<?php

/**
 *
 * @author  2021 Marc Burchart <marc.burchart@tu-dortmund.de> , Kooperative Systeme, FernUniversität Hagen
 * 
 */

namespace mod_hypercast\permission;

defined('MOODLE_INTERNAL') || die();

class course extends context {
    
    function __construct($userid, $courseid){
        $context = \context_course::instance($courseid);        
        parent::__construct($userid, $context);
    }

}

?>
