<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/externallib.php");

class mod_hypercast_external extends external_api {

    public static function isModerator_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of cwr', VALUE_REQUIRED)
            )
        );
    }

    public static function isModerator($cmid) {
            global $DB, $USER;
            $cm = get_coursemodule_from_id('hypercast', $cmid, 0, false, MUST_EXIST);
            $data = $DB->get_record('hypercast', array('id' => $cm->instance), '*', MUST_EXIST);
            $course = $data->course;
            $permission = new mod_hypercast\permission\course($USER->id, $course);
            return array(
                'success' => $permission->isAnyKindOfModerator()
            );
    }

    public static function isModerator_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable')
            )
        );
    }

    public static function isModerator_is_allowed_from_ajax() {
        return true;
    }


    public static function getStatistics_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of cwr', VALUE_REQUIRED)
            )
        );
    }

    public static function getStatistics($cmid) {
        try {
            global $DB, $USER;
            $cm = get_coursemodule_from_id('hypercast', $cmid, 0, false, MUST_EXIST);
            $data = $DB->get_record('hypercast', array('id' => $cm->instance), '*', MUST_EXIST);
            $course = $data->course;
            $permission = new mod_hypercast\permission\course($USER->id, $course);
            if (!$permission->isAnyKindOfModerator()) {
                throw new Exception('No permission');
            }

            $sql = 'SELECT LE.*, U.firstname, U.lastname, G.name AS groupname FROM public.mdl_hyper_log_entry LE INNER JOIN mdl_user U ON U.id = LE.userid LEFT OUTER JOIN mdl_hyper_groups G ON LE.groupid = G.id WHERE LE.event != \'progress\' ORDER BY LE.timecreated DESC';
            $feed = $DB->get_records_sql($sql);

            return array(
                'success' => true,
                'message' => '',
                'data' => $feed
            );
        } catch (Exception $ex) {
            return array(
                'success' => false,
                'message' => json_encode($ex->getMessage())
            );
        }
    }

    public static function getStatistics_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success Variable'),
                'message' => new external_value(PARAM_TEXT, 'Message to response'),
                'data' => new external_multiple_structure(
                    new external_single_structure(array(
                            'id' => new external_value(PARAM_INT, 'Log entry id'),
                            'userid' => new external_value(PARAM_INT, 'UserId'),
                            'groupid' => new external_value(PARAM_INT, 'GroupId'),
                            'usermodified' => new external_value(PARAM_INT, 'UserId'),
                            'timecreated' => new external_value(PARAM_INT, 'Time the entry was created'),
                            'timemodified' => new external_value(PARAM_INT, 'Time the entry was modified'),
                            'event' => new external_value(PARAM_TEXT, 'Key for event category'),
                            'data' => new external_value(PARAM_TEXT, 'Additional data for event'),
                            'firstname' => new external_value(PARAM_TEXT, 'Firstname of user'),
                            'lastname' => new external_value(PARAM_TEXT, 'Lastname of user'),
                            'groupname' => new external_value(PARAM_TEXT, 'Groupname')
                        )
                    )
                , 'Feed data', false)
            )
        );
    }

    public static function getStatistics_is_allowed_from_ajax() {
        return true;
    }

    // helper functions

    public static function getUserDetails($userid) {
        $userrecord = \core_user::get_user($userid);

        $userarray = array(
            'id' => $userrecord->id,
            'firstname' => $userrecord->firstname,
            'lastname' => $userrecord->lastname,
            'username' => $userrecord->username,
            'profileimageurl' => self::getProfileImage($userrecord, 2)
        );

        return $userarray;
    }

    public static function getUserDetails_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'user id of group member'),
                'firstname' => new external_value(PARAM_TEXT, 'first name of group member'),
                'lastname' => new external_value(PARAM_TEXT, 'last name of group member'),
                'username' => new external_value(PARAM_TEXT, 'username of group member'),
                'profileimageurl' => new external_value(PARAM_RAW, 'profile image url')
            ), 'user details');
    }

    public static function getGroupMembers($groupid) {
        global $DB;

        // get userID of each group member
        $sql = 'SELECT userid FROM {hyper_groups_users} WHERE groupid = :p_id';
        $members = $DB->get_records_sql($sql, array('p_id' => $groupid));

        // build an array of members and their info
        $membersarray = array();

        foreach ($members as $member) {
            $membersarray[] = self::getUserDetails($member->userid);
        }

        return $membersarray;

    }

    public static function getCourseIDFromCMID($cmid) {
        $hypercast = self::getHypercastFromCMID($cmid);
        return $hypercast->course;
    }

    public static function populateGroupDetails($group, $usercreated, $members) {
        return array(
            'id' => $group->id,
            'courseid' => $group->courseid,
            'name' => $group->name,
            'description' => $group->description,
            'usercreated' => $usercreated,
            'visible' => $group->visible,
            'maxsize' => $group->maxsize,
            'members' => $members
        );
    }

    /**
     * @param $cmid
     * @return mixed
     */
    public static function getHypercastFromCMID($cmid) {
        global $DB;
        $cm = get_coursemodule_from_id('hypercast', $cmid, 0, false, MUST_EXIST);
        return $DB->get_record('hypercast', array('id' => $cm->instance), '*', MUST_EXIST);
    }

    public static function getProfileImage($userrecord, $picturesize) {
        global $PAGE;
        if ($userrecord->picture) {
            $userpicture = new \user_picture($userrecord);
            $userpicture->size = $picturesize;
            $profileimageurl = $userpicture->get_url($PAGE)->out();
        } else {
            // the url of the moodle-wide default img if no profile image is provided
            $userpicture = new \moodle_url("/pix/u/f" . $picturesize . ".png");
            $profileimageurl = $userpicture->out();
        }
        return $profileimageurl;
    }


        // GET All Comments per Hypercast and optional per Group 

        public static function getAllComments_parameters() {
            return new external_function_parameters(
                array(
                    'cmid' => new external_value(PARAM_INT, 'id of course module'),
                    'groupid' => new external_value(PARAM_INT, 'id of group'),
                )
            );
        }
    
        public static function getAllComments_is_allowed_from_ajax() {
            return true;
        }
    
        public static function getAllComments($cmid, $groupid) {
            global $DB;
    
            $hypercastid = self::getHypercastFromCMID($cmid)->id;

            if($groupid==-1){
                $sql = "SELECT * from {hyper_comments} WHERE referenceid is null AND deleted =0 AND hypercastid = :p_hid";
                $records = $DB->get_records_sql($sql, array('p_hid' => $hypercastid));
            } else {
                $sql = "SELECT * from {hyper_comments} WHERE referenceid is null AND deleted =0 AND hypercastid = :p_hid AND groupid = :p_gid";
                $records = $DB->get_records_sql($sql, array('p_hid' => $hypercastid, 'p_gid' => $groupid));
            }
    
            $comments = array();
            foreach ($records as $record) {
                $comments[] =  self::populateComments($record);
            }
    
            return $comments;
        }
        public static function populateComments($record) {
            return array(
                'groupid' => $record->groupid,
                'timestamp' => $record->timestamp,
                'deleted' => $record->deleted,
                'category' => $record->category,
            );
            }   
        public static function getAllComments_returns() {
            return new external_multiple_structure(
                new external_single_structure(
                array(
                    'groupid' => new external_value(PARAM_INT, 'id of group'),
                    'timestamp' =>  new external_value(PARAM_INT, 'timestamp'),
                    'deleted' => new external_value(PARAM_INT, 'Flag deleted'),
                    'category' => new external_value(PARAM_TEXT, 'Category'),
                    )
            ));
        }

    // Save Log-Entry

    public static function save_log_entry_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'id of user'),
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'event' => new external_value(PARAM_TEXT, 'event'),
                'data' => new external_value(PARAM_TEXT, 'parameters'),
            )
        );
    }

    public static function save_log_entry_is_allowed_from_ajax() {
        return true;
    }


    /**
     * @param $userid
     * @param $groupid
     * @param $event
     * @param $data
     * @return void
     */
    public static function save_log_entry($userid, $groupid, $event, $data):void {
        global $DB;
        $log_table_name = 'hyper_log_entry';
        $log_entry = new \stdClass();
        $log_entry->userid = $userid;
        $log_entry->usermodified = $userid;
        $log_entry->groupid = $groupid;
        $now = time();
        $log_entry->timecreated = $now;
        $log_entry->timemodified = $now;
        $log_entry->event = $event;
        $log_entry->data = $data;
        try {
            $DB->insert_record($log_table_name, $log_entry);
        } catch (dml_exception $e) {
            echo $e->getMessage();
        }
    }

    public static function save_log_entry_returns() {    }

   
    // Get enrolled user

    public static function getEnrolledUser_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of cwr', VALUE_REQUIRED)
            )
        );
    }

    public static function getEnrolledUser($cmid) {

            $courseid= self::getCourseIDFromCMID($cmid);
            global $DB, $USER;
            $sql ="SELECT u.id as stud FROM mdl_user u ";
            $sql = $sql . "INNER JOIN mdl_role_assignments ra ON ra.userid = u.id ";
            $sql = $sql . "INNER JOIN mdl_context ct ON ct.id = ra.contextid ";
            $sql = $sql . "INNER JOIN mdl_course c ON c.id = ct.instanceid AND c.id=" . $courseid;
            $sql = $sql . " INNER JOIN mdl_role r ON r.id = ra.roleid ";
            $sql = $sql . "INNER JOIN mdl_course_categories cc ON cc.id = c.category ";
            $sql = $sql . "WHERE r.id =5 ";

            $records = $DB->get_records_sql($sql);

            $result = array();
            foreach ($records as $record) {
                $result[] =  self::populateUser($record);
            }

           return $result;
        }


        public static function populateUser($record) {
            return array(
                'id' => $record->stud,
            );
        }
        
    
        public static function getEnrolledUser_returns() {
            return new external_multiple_structure(
                new external_single_structure(
                array(
                 'id' => new external_value(PARAM_INT, 'id of enrolled students'),
                )));}
    

    public static function getEnrolledUser_is_allowed_from_ajax() {
        return true;
    }

}

