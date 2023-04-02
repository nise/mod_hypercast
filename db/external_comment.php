<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/externallib.php");
include('external.php');

class mod_hypercast_external_comment extends external_api {

    // Create Comments

    public static function createComment_parameters() {
        return new external_function_parameters(
            array(
                'comment' => new external_value(PARAM_TEXT, 'comment text'),
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
                'timestamp' => new external_value(PARAM_INT, 'timestamp of audiofile'),
                'referenceid' => new external_value(PARAM_INT, 'id commented comment', false),
                'category' => new external_value(PARAM_TEXT, 'category of comment', false)
            )
        );
    }

    public static function createComment_is_allowed_from_ajax() {
        return true;
    }

    public static function createComment($comment, $groupid, $cmid, $timestamp, $referenceid, $category) {
        global $CFG, $DB, $USER;

        // get hypercast instance id
        $hypercastInstanceid = mod_hypercast_external::getHypercastFromCMID($cmid)->id;

        $user_is_group_user = $DB->get_record('hyper_groups_users', array('userid' => $USER->id, 'groupid' => $groupid));
        if (!$user_is_group_user) {
            throw new Exception('user does not exist in group');
        }

        if ($referenceid != null) {
            $referenced_comment_exists = $DB->get_record('hyper_comments', array('id' => $referenceid));
            if (!$referenced_comment_exists) {
                throw new Exception('referenced comment does not exist');
            }
        }

        $hyper_comment = new \stdClass();
        $hyper_comment->comment = $comment;
        $hyper_comment->groupid = $groupid;
        $hyper_comment->userid = $USER->id;
        $hyper_comment->hypercastid = $hypercastInstanceid;
        $hyper_comment->timestamp = $timestamp;
        $hyper_comment->referenceid = $referenceid;
        $hyper_comment->category = $category;

        $now = time();
        $hyper_comment->timecreated = $now;
        $hyper_comment->timemodified = $now;

        $id = $DB->insert_record('hyper_comments', $hyper_comment);

        $group = $DB->get_record('hyper_groups', array('id' => $groupid));
        $data = json_encode(
            array(
                'commentid' => $id,
                'groupname' => $group->name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $groupid, 'create_comment', $data);

        return array(
            'id' => $id,
            'comment' => $hyper_comment->comment,
            'groupid' => $hyper_comment->groupid,
            'usercreated' => $hyper_comment->userid,
            'timestamp' => $hyper_comment->timestamp,
            'referenceid' => $hyper_comment->referenceid,
            'category' => $hyper_comment->category
        );
    }

    public static function createComment_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of group'),
                'comment' => new external_value(PARAM_TEXT, 'comment text'),
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'usercreated' => new external_value(PARAM_INT, 'user id of creator of group'),
                'timestamp' => new external_value(PARAM_INT, 'timestamp of audiofile'),
                'referenceid' => new external_value(PARAM_INT, 'id commented comment'),
                'category' => new external_value(PARAM_TEXT, 'category of comment')
            )
        );
    }

    // GET All Timestamps for Comments per Group

    public static function getCommentsTimestamps_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
                'groupid' => new external_value(PARAM_INT, 'id of group'),
            )
        );
    }

    public static function getCommentsTimestamps_is_allowed_from_ajax() {
        return true;
    }

    public static function getCommentsTimestamps($cmid, $groupid) {
        global $DB;

        $hypercastid = mod_hypercast_external::getHypercastFromCMID($cmid)->id;

        $sql = "SELECT DISTINCT timestamp from {hyper_comments} WHERE hypercastid = :p_hid AND groupid = :p_gid";
        $records = $DB->get_records_sql($sql, array('p_hid' => $hypercastid, 'p_gid' => $groupid));

        $timestamps = array();
        foreach ($records as $record) {
            $timestamps[] = $record->timestamp;
        }

        return array(
            'cmid' => $cmid,
            'groupid' => $groupid,
            'timestamps' => $timestamps
        );
    }

    public static function getCommentsTimestamps_returns() {
        return new external_single_structure(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'timestamps' => new external_multiple_structure(new external_value(PARAM_INT, 'timestamp'))
            )
        );
    }

    // GET All Comments for Group and Timestamp

    public static function getComments_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'id of course module'),
                'groupid' => new external_value(PARAM_INT, 'id of group'),
                'timestamp' => new external_value(PARAM_INT, 'timestamp of audiofile')
            )
        );
    }

    public static function getComments_is_allowed_from_ajax() {
        return true;
    }

    public static function getComments_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of group'),
                    'comment' => new external_value(PARAM_TEXT, 'comment text'),
                    'groupid' => new external_value(PARAM_INT, 'id of group'),
                    'user' => mod_hypercast_external::getUserDetails_returns(),
                    'timecreated' => new external_value(PARAM_INT, 'time when comment was created'),
                    'timemodified' => new external_value(PARAM_INT, 'time when comment was modified'),
                    'timestamp' => new external_value(PARAM_INT, 'timestamp of audiofile'),
                    'deleted' => new external_value(PARAM_BOOL, 'vaule of deletion status'),
                    'referenceid' => new external_value(PARAM_INT, 'id commented comment'),
                    'category' => new external_value(PARAM_TEXT, 'category of comment')
                )
            )
        );
    }

    public static function getComments($cmid, $groupid, $timestamp) {
        global $DB;

        $hypercastInstanceid = mod_hypercast_external::getHypercastFromCMID($cmid)->id;

        $comments = $DB->get_records('hyper_comments',
            array('hypercastid' => $hypercastInstanceid, 'groupid' => $groupid, 'timestamp' => $timestamp));

        $result = array();

        foreach ($comments as $comment) {
            $comment->user = mod_hypercast_external::getUserDetails($comment->userid);
            $result[] = $comment;
        }

        return $result;
    }

    // Update Comment

    public static function updateComment_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id of comment'),
                'comment' => new external_value(PARAM_TEXT, 'new comment text'),
                'category' => new external_value(PARAM_TEXT, 'category of comment', false)
            )
        );
    }

    public static function updateComment_is_allowed_from_ajax() {
        return true;
    }

    public static function updateComment_returns() {
        return new external_function_parameters(
            array(
                'success' => new external_value(PARAM_BOOL, 'id of comment')
            )
        );
    }

    /**
     * @throws Exception
     */
    public static function updateComment($id, $comment, $category) {
        global $DB, $USER;

        $hyper_comment = self::getComment($id);

        // check for author of comment
        if ($hyper_comment->userid != $USER->id) {
            throw new Exception("You are not the author of this comment");
        }

        $hyper_comment->comment = $comment;
        $hyper_comment->category= $category;
        

        $result = self::updateCommentRecord($hyper_comment);

        $group = $DB->get_record('hyper_groups', array('id' => $hyper_comment->groupid));
        $data = json_encode(
            array(
                'commentid' => $id,
                'groupname' => $group->name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $group->id, 'update_comment', $data);
        
        return array(
            'success' => $result
        );
    }

    // Delete Comment

    public static function deleteComment_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id of comment')
            )
        );
    }

    public static function deleteComment_is_allowed_from_ajax() {
        return true;
    }

    public static function deleteComment_returns() {
        return new external_function_parameters(
            array(
                'success' => new external_value(PARAM_BOOL, 'id of comment')
            )
        );
    }

    /**
     * @throws Exception
     */
    public static function deleteComment($id) {
        global $DB, $USER;

        $hyper_comment = self::getComment($id);

        // check for author of comment
        if ($hyper_comment->userid != $USER->id) {
            throw new Exception("You are not the author of this comment");
        }

        $hyper_comment->deleted = '1';

        $result = self::updateCommentRecord($hyper_comment);


        $group = $DB->get_record('hyper_groups', array('id' => $hyper_comment->groupid));
        $data = json_encode(
            array(
                'commentid' => $id,
                'groupname' => $group->name
            )
        );
        mod_hypercast_external::save_log_entry($USER->id, $group->id, 'delete_comment', $data);

        return array(
            'success' => $result
        );
    }
    public static function getComment($id) {
        global $DB;
        $hyper_comment = $DB->get_record('hyper_comments', array('id' => $id));

        // check if comment exists
        if (!$hyper_comment) {
            throw new Exception("Comment with id $id does not exist");
        }
        return $hyper_comment;
    }

    public static function updateCommentRecord($hyper_comment) {
        global $DB;

        $now = time();
        $hyper_comment->timemodified = $now;

        return $DB->update_record('hyper_comments', $hyper_comment);
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
    
            $hypercastid = mod_hypercast_external::getHypercastFromCMID($cmid)->id;

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


}

