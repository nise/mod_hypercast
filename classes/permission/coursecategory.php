<?php

/**
 *
 * @author  2021 Marc Burchart <marc.burchart@tu-dortmund.de> , Kooperative Systeme, FernUniversität Hagen
 * 
 */

namespace mod_hypercast\permission;

defined('MOODLE_INTERNAL') || die();

class coursecategory extends context {
    function __construct($userid, $category_id){
        $context = \context_coursecat::instance($category_id);
        parent::__construct($userid, $context);
    }
}

?>
