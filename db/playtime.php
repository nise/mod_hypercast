<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/externallib.php");
include 'external.php';

/**
 * hypercast audio player services
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_hypercast_playtime extends external_api {

    // Get playtime
    public static function getPlaytime_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
            )
        );
    }

    public static function getPlaytime_is_allowed_from_ajax() {
        return true;
    }

    public static function getPlaytime($cmid) {
        global $DB, $USER;

        $hypercastid = mod_hypercast_external::getHypercastFromCMID($cmid)->id;
        $userid = $USER->id;

        $table_name = 'hyper_audio_progress';
        $entry = $DB->get_record($table_name, array('hypercastid' => $hypercastid, 'userid' => $userid));
        return array(
            'timestamp' => $entry ? $entry->timestamp : 0
        );
    }

    public static function getPlaytime_returns() {
        return new external_single_structure(
            array(
                'timestamp' => new external_value(PARAM_INT, 'current playtime of user for hyperaudio')
            )
        );
    }

    // Save playtime

    public static function savePlaytime_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
                'timestamp' => new external_value(PARAM_INT, 'current playtime'),
                'groupid' => new external_value(PARAM_INT, 'current group'),
                'live' => new external_value(PARAM_BOOL, 'progress was made during live session')
            )
        );
    }

    public static function savePlaytime_is_allowed_from_ajax() {
        return true;
    }

    public static function savePlaytime($cmid, $timestamp, $groupid, $live) {
        global $DB, $USER;

        $hypercast = mod_hypercast_external::getHypercastFromCMID($cmid);
        $userid = $USER->id;

        $table_name = 'hyper_audio_progress';
        $existing_entry = $DB->get_record($table_name, array('hypercastid' => $hypercast->id, 'userid' => $userid));
        if ($existing_entry) {
            $existing_entry->timestamp = $timestamp;
            $existing_entry->timemodified = time();
            $id = $DB->update_record($table_name, $existing_entry, false);
        } else {
            $userPlaytime = new \stdClass();
            $userPlaytime->hypercastid = $hypercast->id;
            $userPlaytime->userid = $userid;
            $userPlaytime->timestamp = $timestamp;
            $userPlaytime->usermodified = $userid;;

            $now = time();
            $userPlaytime->timecreated = $now;
            $userPlaytime->timemodified = $now;

            $id = $DB->insert_record($table_name, $userPlaytime);
        }

        $data = json_encode(
            array(
                'live' => $live,
                'progress' => $timestamp
            )
        );
        mod_hypercast_external::save_log_entry($userid, $groupid, 'progress', $data);

        return array(
            'id' => $id
        );
    }

    public static function savePlaytime_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of entry')
            )
        );
    }

    // Get group members playtime
    public static function getGroupMembersPlaytime_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
                'groupid' => new external_value(PARAM_INT, 'id of group')
            )
        );
    }

    public static function getGroupMembersPlaytime_is_allowed_from_ajax() {
        return true;
    }

    public static function getGroupMembersPlaytime($cmid, $groupid) {
        global $DB;

        $hypercastid = mod_hypercast_external::getHypercastFromCMID($cmid)->id;
        $sql = 'SELECT A.userid, B.timestamp FROM mdl_hyper_groups_users A INNER JOIN mdl_hyper_audio_progress B ON A.userid = B.userid WHERE B.hypercastid = :p_hypercastid AND A.groupid = :p_groupid AND A.hideuser = 0';
        $members = $DB->get_records_sql($sql, array('p_hypercastid' => $hypercastid, 'p_groupid' => $groupid));

        return array(
            'membersPlaytime' => $members
        );
    }

    public static function getGroupMembersPlaytime_returns() {
        return new external_single_structure(
            array(
                'membersPlaytime' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'userid' => new external_value(PARAM_INT, 'user id of group member'),
                            'timestamp' => new external_value(PARAM_INT, 'current playtime of user for hyperaudio')
                        ), 'user playtime')
                )
            )
        );
    }
}