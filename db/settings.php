<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/externallib.php");
include 'external.php';

/**
 * hypercast audio player & group settings
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class mod_hypercast_settings extends external_api {

    public static function getPrivacySettings_parameters() {
        return new external_function_parameters(
            array(
                'groupid' => new external_value(PARAM_INT, 'id of group')
            )
        );
    }

    public static function getPrivacySettings($groupid) {
        global $DB, $USER;

        $usersettings = $DB->get_record('hyper_groups_users', array('groupid' => $groupid, 'userid' => $USER->id), '*', MUST_EXIST);

        return array(
            'groupid' => $groupid,
            'hideUser' => $usersettings->hideuser,
            'hideOthers' => $usersettings->hideothers,
            'audioCues' => $usersettings->audiocues,
        );
    }

    public static function getPrivacySettings_returns() {
        return self::PrivacySettings_returns();
    }

    public static function getPrivacySettings_is_allowed_from_ajax() {
        return true;
    }

    public static function savePrivacySettings_parameters() {
        return new external_function_parameters(
            array(
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'hideUser' => new external_value(PARAM_BOOL, 'hide User'),
                'hideOthers' => new external_value(PARAM_BOOL, 'hide Others'),
                'audioCues' => new external_value(PARAM_RAW, 'settings for audio cues')
            )
        );
    }

    public static function savePrivacySettings($groupid, $hideuser, $hideothers, $audiocues) {
        global $DB, $USER;

        $usersettings = $DB->get_record('hyper_groups_users', array('groupid' => $groupid, 'userid' => $USER->id), '*', MUST_EXIST);

        $usersettings->hideuser = $hideuser;
        $usersettings->hideothers = $hideothers;
        $usersettings->audiocues = $audiocues;

        $now = time();
        $usersettings->timemodified = $now;

        $id = $DB->update_record('hyper_groups_users', $usersettings);

        return array(
            'groupid' => $groupid,
            'hideUser' => $usersettings->hideuser,
            'hideOthers' => $usersettings->hideothers,
            'audioCues' => $usersettings->audiocues,
        );
    }

    public static function savePrivacySettings_returns() {
        return self::PrivacySettings_returns();
    }

    public static function savePrivacySettings_is_allowed_from_ajax() {
        return true;
    }

    // helper functions
    public static function PrivacySettings_returns() {
        return new external_single_structure(
            array(
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'hideUser' => new external_value(PARAM_BOOL, 'hide User'),
                'hideOthers' => new external_value(PARAM_BOOL, 'hide Others'),
                'audioCues' => new external_value(PARAM_RAW, 'settings for audio cues')
            )
        );
    }

}