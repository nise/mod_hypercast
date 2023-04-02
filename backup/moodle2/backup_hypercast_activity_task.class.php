<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/hypercast/backup/moodle2/backup_hypercast_stepslib.php');


class backup_hypercast_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        // do nothing
    }

    static public function encode_content_links($content) {
        // do nothing
        return $content;
    }

}