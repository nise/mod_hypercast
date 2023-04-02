<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/externallib.php");
include('external.php');

class mod_hypercast_external_groups extends external_api {

    // Create Group

    public static function createGroup_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
                'name' => new external_value(PARAM_TEXT, 'name of group'),
                'description' => new external_value(PARAM_TEXT, 'description of group'),
                'visible' => new external_value(PARAM_BOOL, 'group is visible for other users'),
                'maxsize' => new external_value(PARAM_INT, 'maximum size of group'),
                'onlyTry' => new external_value(PARAM_BOOL, 'just try dont save')
            )
        );
    }

    public static function createGroup_is_allowed_from_ajax() {
        return true;
    }
    

    public static function createGroup($cmid, $name, $description, $visible, $maxsize, $onlyTry) {
        global $CFG, $DB, $USER;

        // get course id from course module id
        $courseid = self::getCourseIDFromCMID($cmid);

        $duplicate_name_for_course = $DB->get_record('hyper_groups', array('courseid' => $courseid, 'name' => $name));

        // Return from Call for Evaluation
        if ($duplicate_name_for_course){
            throw new Exception('group with same name already exists in this course');
        }

        if ($onlyTry){
            return array(
                'id' => $id,
                'courseid' => $group->courseid,
                'name' => $group->name,
                'description' => $group->description,
                'usercreated' => $group->usercreated,
                'visible' => $group->visible,
                'maxsize' => $group->maxsize
            );
        }

        $group = new \stdClass();
        $group->courseid = $courseid;
        $group->name = $name;
        $group->description = $description;
        $group->usercreated = $USER->id;
        $group->visible = $visible;
        $group->maxsize = $maxsize;

        $now = time();
        $group->timecreated = $now;
        $group->timemodified = $now;

        $id = $DB->insert_record('hyper_groups', $group);

        $group_user = new \stdClass();
        $group_user->userid = $USER->id;
        $group_user->groupid = $id;
        $group_user->usermodified = $USER->id;
        $group_user->timecreated = $now;
        $group_user->timemodified = $now;

        $DB->insert_record('hyper_groups_users', $group_user);

        $data = json_encode(
            array(
                'groupname' => $name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $id, 'create_group', $data);

        return array(
            'id' => $id,
            'courseid' => $group->courseid,
            'name' => $group->name,
            'description' => $group->description,
            'usercreated' => $group->usercreated,
            'visible' => $group->visible,
            'maxsize' => $group->maxsize
        );
    }

    public static function createGroup_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of group'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'name' => new external_value(PARAM_TEXT, 'name of group'),
                'description' => new external_value(PARAM_TEXT, 'description of group'),
                'usercreated' => new external_value(PARAM_INT, 'user id of creator of group'),
                'visible' => new external_value(PARAM_BOOL, 'group is visible for other users'),
                'maxsize' => new external_value(PARAM_INT, 'maximum size of group'),
            )
        );
    }

    // Delete Group

    public static function deleteGroup_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id of group'),
            )
        );
    }

    /**
     * Deletes a given group for the database and removes all its memberships. Only its creator can delete a group.
     *
     * @param int $id groupid of the group
     * @return array $id groupid of the group which was deleted
     */
    public static function deleteGroup($id) {
        global $DB, $USER;

        //get group owner
        $group = $DB->get_record('hyper_groups', array('id' => $id));
        $usercreated = $group->usercreated;

        if ($usercreated != $USER->id) {
            throw new Exception('current user cannot delete group');
        }

        // records from all tables linking to groupid as fk have to be deleted before deleting the group
        $DB->delete_records('hyper_comments', array('groupid' => $id));
        $DB->delete_records('hyper_groups_users', array('groupid' => $id));
        $DB->delete_records('hyper_groups', array('id' => $id));

        $data = json_encode(
            array(
                'groupname' => $group->name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $id, 'delete_group', $data);

        return array(
            'id' => $id
        );

    }

    public static function deleteGroup_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of group'),
            )
        );
    }

    public static function deleteGroup_is_allowed_from_ajax() {
        return true;
    }


    // get group details

    public static function getGroupDetails_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id of group'),
            )
        );
    }


    /**
     * Gets group details for a given group
     *
     * @param int $id groupid of the group in question
     * @return array information about the group from the database record, actual group size and list of members with first and last name
     * @throws dml_exception
     */
    public static function getGroupDetails($id) {
        global $DB;

        // get group record
        $group = $DB->get_record('hyper_groups', array('id' => $id));

        $usercreated = self::getUserDetails($group->usercreated);
        $members = self::getGroupMembers($id);

        return self::populateGroupDetails($group, $usercreated, $members);

    }

    public static function getGroupDetails_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of group'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'name' => new external_value(PARAM_TEXT, 'name of group'),
                'description' => new external_value(PARAM_TEXT, 'description of group'),
                'usercreated' => self::getUserDetails_returns(),
                'visible' => new external_value(PARAM_BOOL, 'group is visible for other users'),
                'maxsize' => new external_value(PARAM_INT, 'maximum size of group'),
                'members' => new external_multiple_structure(
                    self::getUserDetails_returns(), 'list of group members')
            )
        );

    }

    public static function getGroupDetails_is_allowed_from_ajax() {
        return true;
    }


    // get all groups' details

    public static function getAllGroupDetails_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module')
            )
        );
    }

    /**
     * Retrieves information about all groups in the current course (across all course modules)
     *
     * @param int cmid of the course module
     * @return array returns an array of all groups, containing group info and member info
     */
    public static function getAllGroupDetails($cmid) {
        global $DB;

        $courseid = self::getCourseIDFromCMID($cmid);

        $groups = $DB->get_records('hyper_groups', array('courseid' => $courseid));

        $result_groups = array();

        foreach ($groups as $group) {
            $usercreated = self::getUserDetails($group->usercreated);
            $members = self::getGroupMembers($group->id);
            $result_groups[] = self::populateGroupDetails($group, $usercreated, $members);
        }

        return $result_groups;
    }

    public static function getAllGroupDetails_returns() {
        return new external_multiple_structure(
            self::getGroupDetails_returns()
        );
    }

    public static function getAllGroupDetails_is_allowed_from_ajax() {
        return true;
    }


    // UPDATE GROUP

    public static function updateGroup_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id of group'),
                'name' => new external_value(PARAM_TEXT, 'name of group'),
                'description' => new external_value(PARAM_TEXT, 'description of group'),
                'visible' => new external_value(PARAM_BOOL, 'group is visible for other users'),
                'maxsize' => new external_value(PARAM_INT, 'maximum size of group')
            )
        );
    }

    /**
     * Update group information in database. If certain values should not be changed, pass the existing values to the function
     *
     * @param int id of group
     * @param string new name of group
     * @param string new description of group
     * @param int new visibity of group
     * @param int new maximum size of group
     * @return array updated record for group with all existing and updated details
     */
    public static function updateGroup($id, $name, $description, $visible, $maxsize) {
        global $DB, $USER;

        $group = $DB->get_record('hyper_groups', array('id' => $id));

        // check if group exists
        if (!$group) {
            throw new UnexpectedValueException('group cannot be found');
        }
        // check if user attempting to modify group is the owner of the group
        if ($group->usercreated != $USER->id) {
            throw new Exception('group details cannot be changed by current user');
        }
        // check for duplicate names within the course in case a new name is given
        if ($group->name != $name) {
            $duplicate_name_for_course = $DB->get_record('hyper_groups', array('courseid' => $group->courseid, 'name' => $name));
            if ($duplicate_name_for_course) {
                throw new Exception('group with same name already exists in this course');
            }
        }

        // update group record
        $group->name = $name;
        $group->description = $description;
        $group->maxsize = $maxsize;
        $group->visible = $visible;

        $now = time();
        $group->timemodified = $now;

        $id = $DB->update_record('hyper_groups', $group);

        $data = json_encode(
            array(
                'groupname' => $group->name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $id, 'update_group', $data);

        return array(
            'id' => $id,
            'courseid' => $group->courseid,
            'name' => $group->name,
            'description' => $group->description,
            'usercreated' => $group->usercreated,
            'visible' => $group->visible,
            'maxsize' => $group->maxsize
        );
    }

    public static function updateGroup_returns() {
        return self::createGroup_returns();
    }

    public static function updateGroup_is_allowed_from_ajax() {
        return true;
    }

    // Join a public group

    public static function joinGroup_parameters() {
        return new external_function_parameters(
            array(
                'groupid' => new external_value(PARAM_INT, 'id of group')
            )
        );
    }

    public static function joinGroup($groupid) {
        global $DB, $USER;

        $group = (object) self::getGroupDetails($groupid);
        if (!$group->id) {
            throw new Exception('group does not exist');
        }
        // already a member -> just return object because that is not a real error
        $is_member = $DB->get_record('hyper_groups_users', array('groupid' => $groupid, 'userid' => $USER->id));
        if ($is_member) {
            return array('userid' => $USER->id, 'groupid' => $groupid);
        }
        // group is already full
        if (count($group->members) >= $group->maxsize) {
            throw new Exception('group is already full');
        }

        $now = time();
        $group_user = new \stdClass();
        $group_user->userid = $USER->id;
        $group_user->groupid = $group->id;
        $group_user->usermodified = $USER->id;
        $group_user->timecreated = $now;
        $group_user->timemodified = $now;

        $DB->insert_record('hyper_groups_users', $group_user);


        $data = json_encode(
            array(
                'groupname' => $group->name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $group->id, 'join_group', $data);

        return array(
            'userid' => $group_user->userid,
            'groupid' => $group_user->groupid,
            'timecreated' => $group_user->timecreated,
        );
    }

    public static function joinGroup_returns() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'id of user'),
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'timecreated' => new external_value(PARAM_INT, 'time when dataset was inserted', VALUE_OPTIONAL),
            )
        );
    }

    public static function joinGroup_is_allowed_from_ajax() {
        return true;
    }

    // Leave a group
    public static function leaveGroup_parameters() {
        return new external_function_parameters(
            array(
                'groupid' => new external_value(PARAM_INT, 'id of group')
            )
        );
    }

    public static function leaveGroup($groupid){
        global $DB, $USER;

        $group = (object) self::getGroupDetails($groupid);
        if (!$group->id) {
            throw new Exception('group does not exist');
        }

        // MUST_EXIST throws exception if no record is found
        $DB->get_record('hyper_groups_users', array('groupid' => $groupid, 'userid' => $USER->id), '*', MUST_EXIST);

        $result = $DB->delete_records('hyper_groups_users', array('groupid' => $groupid, 'userid' => $USER->id));

        $data = json_encode(
            array(
                'groupname' => $group->name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $groupid, 'leave_group', $data);

        return array(
            'success' => $result
        );

    }

    public static function leaveGroup_returns() {
        return new external_function_parameters(
            array(
                'success' => new external_value(PARAM_BOOL, 'success value')
            )
        );
    }

    public static function leaveGroup_is_allowed_from_ajax() {
        return true;
    }



    // helper functions

    public static function getUserDetails($userid) {
        global $PAGE;

        $picturesize = 2;


        $userrecord = \core_user::get_user($userid);

        if ($userrecord->picture) {
            $userpicture = new \user_picture($userrecord);
            $userpicture->size = $picturesize;
            $profileimageurl = $userpicture->get_url($PAGE)->out();
        } else {
            // the url of the moodle-wide default img if no profile image is provided
            $userpicture = new \moodle_url("/pix/u/f" . $picturesize . ".png");
            $profileimageurl = $userpicture->out();
        }

        $userarray = array(
            'id' => $userrecord->id,
            'firstname' => $userrecord->firstname,
            'lastname' => $userrecord->lastname,
            'profileimageurl' => $profileimageurl
        );

        return $userarray;
    }


    public static function getCourseIDFromCMID($cmid) {
        $hypercast = mod_hypercast_external::getHypercastFromCMID($cmid);
        return $hypercast->course;
    }

    public static function getUserDetails_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'user id of group member'),
                'firstname' => new external_value(PARAM_TEXT, 'first name of group member'),
                'lastname' => new external_value(PARAM_TEXT, 'last name of group member'),
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

    
    public static function getGroupProgress_parameters() {
        return new external_function_parameters(
            array(
                'groupid' => new external_value(PARAM_INT, 'id of group')
            )
        );
    }
    

    
/**
 * Retrieves information about groups activity 
 *
 * @param int groupid of the of the group
 * @return array returns an array of all progress-events
 */
public static function  getGroupProgress($groupid) {
    global $DB;

    if ($groupid==-1){
        $events=$DB->get_records('hyper_log_entry', array('event' => 'progress'));
    } else {
        $events=$DB->get_records('hyper_log_entry', array('groupid' => $groupid, 'event' => 'progress'));
    }

    $result = array();

    foreach($events as $event) {
        $result[] = self::populateProgressDetails($event);
    }

    return $result;
}

public static function populateProgressDetails($event) {
    return array(
        'data' => $event->data,
        'timecreated' => $event->timecreated,
        'timemodified' => $event->timemodified,
        'userid' => $event->userid,
    );
}

public static function  getGroupProgress_returns() {
    return new external_multiple_structure(
        new external_single_structure(
            array(
            'data' => new external_value(PARAM_TEXT, 'JSON of EventData'),
            'timecreated' => new external_value(PARAM_INT, 'time when event happened'),
            'timemodified' => new external_value(PARAM_INT, 'time when event was modified'),
            'userid' => new external_value(PARAM_INT, 'id of user'),
        )
    ));
}

public static function  getGroupProgress_is_allowed_from_ajax() {
    return true;
}
}
