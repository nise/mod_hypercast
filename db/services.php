<?php

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_hypercast_isModerator' => array(
        'classname'   => 'mod_hypercast_external',
        'methodname'  => 'isModerator',
        'classpath'   => 'mod/hypercast/db/external.php',
        'description' => 'Check if user is moderator',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getStatistics' => array(
        'classname'   => 'mod_hypercast_external',
        'methodname'  => 'getStatistics',
        'classpath'   => 'mod/hypercast/db/external.php',
        'description' => 'Get statistics for user with moderator role',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true
    ),
    /* comment services */
    'mod_hypercast_createComment' => array(
        'classname'     => 'mod_hypercast_external_comment',
        'methodname'    => 'createComment',
        'classpath'     => 'mod/hypercast/db/external_comment.php',
        'description'   => 'Create a new comment',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getCommentsTimestamps' => array(
        'classname'     => 'mod_hypercast_external_comment',
        'methodname'    => 'getCommentsTimestamps',
        'classpath'     => 'mod/hypercast/db/external_comment.php',
        'description'   => 'Returns a list of all comments timestamps in a course for a specific group',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getComments' => array(
        'classname'     => 'mod_hypercast_external_comment',
        'methodname'    => 'getComments',
        'classpath'     => 'mod/hypercast/db/external_comment.php',
        'description'   => 'Returns a list of all comments in a course for a specific group and audio timestamp',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_updateComment' => array(
        'classname'     => 'mod_hypercast_external_comment',
        'methodname'    => 'updateComment',
        'classpath'     => 'mod/hypercast/db/external_comment.php',
        'description'   => 'Updates a comment',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_deleteComment' => array(
        'classname'     => 'mod_hypercast_external_comment',
        'methodname'    => 'deleteComment',
        'classpath'     => 'mod/hypercast/db/external_comment.php',
        'description'   => 'Deletes a comment',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),

    


        /* groups services */

        'mod_hypercast_createGroup' => array(
            'classname'     => 'mod_hypercast_external_groups',
            'methodname'    => 'createGroup',
            'classpath'     => 'mod/hypercast/db/external_groups.php',
            'description'   => 'Create a new group',
            'type'          => 'write',
            'ajax'          => true,
            'loginrequired' => true
        ),
    

    'mod_hypercast_deleteGroup' => array(
        'classname'     => 'mod_hypercast_external_groups',
        'methodname'    => 'deleteGroup',
        'classpath'     => 'mod/hypercast/db/external_groups.php',
        'description'   => 'As an owner, delete an existing group',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getGroupDetails' => array(
        'classname'     => 'mod_hypercast_external_groups',
        'methodname'    => 'getGroupDetails',
        'classpath'     => 'mod/hypercast/db/external_groups.php',
        'description'   => 'Retrieve detail information about a given group',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getAllGroupDetails' => array(
        'classname'     => 'mod_hypercast_external_groups',
        'methodname'    => 'getAllGroupDetails',
        'classpath'     => 'mod/hypercast/db/external_groups.php',
        'description'   => 'Retrieve detail information about all groups in a course',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_updateGroup' => array(
        'classname'     => 'mod_hypercast_external_groups',
        'methodname'    => 'updateGroup',
        'classpath'     => 'mod/hypercast/db/external_groups.php',
        'description'   => 'As an owner, update details of a group',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_joinGroup' => array(
        'classname'     => 'mod_hypercast_external_groups',
        'methodname'    => 'joinGroup',
        'classpath'     => 'mod/hypercast/db/external_groups.php',
        'description'   => 'Join a public group',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_leaveGroup' => array(
        'classname'     => 'mod_hypercast_external_groups',
        'methodname'    => 'leaveGroup',
        'classpath'     => 'mod/hypercast/db/external_groups.php',
        'description'   => 'Leave a group',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getAllComments' => array(
        'classname'     => 'mod_hypercast_external',
        'methodname'    => 'getAllComments',
        'classpath'     => 'mod/hypercast/db/external.php',
        'description'   => 'Returns a list of all comments in a course, optional for a specific group',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),



    /* audio player services */
    'mod_hypercast_savePlaytime' => array(
        'classname'     => 'mod_hypercast_playtime',
        'methodname'    => 'savePlaytime',
        'classpath'     => 'mod/hypercast/db/playtime.php',
        'description'   => 'Save current playtime of audio file for user',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getPlaytime' => array(
        'classname'     => 'mod_hypercast_playtime',
        'methodname'    => 'getPlaytime',
        'classpath'     => 'mod/hypercast/db/playtime.php',
        'description'   => 'Get current playtime of audio file for user',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getGroupMembersPlaytime' => array(
        'classname'     => 'mod_hypercast_playtime',
        'methodname'    => 'getGroupMembersPlaytime',
        'classpath'     => 'mod/hypercast/db/playtime.php',
        'description'   => 'Get current playtime of audio file for all group members',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_getPrivacySettings' => array(
        'classname'     => 'mod_hypercast_settings',
        'methodname'    => 'getPrivacySettings',
        'classpath'     => 'mod/hypercast/db/settings.php',
        'description'   => 'Get player privacy settings of user and group',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    'mod_hypercast_savePrivacySettings' => array(
        'classname'     => 'mod_hypercast_settings',
        'methodname'    => 'savePrivacySettings',
        'classpath'     => 'mod/hypercast/db/settings.php',
        'description'   => 'Save player privacy settings of user and group',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),

    'mod_hypercast_getGroupProgress' => array(
        'classname'     => 'mod_hypercast_external_groups',
        'methodname'    => 'getGroupProgress',
        'classpath'     => 'mod/hypercast/db/external_groups.php',
        'description'   => 'Get Playtime of Groups in/out of LiveSession',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    
    'mod_hypercast_save_log_entry' => array(
        'classname'     => 'mod_hypercast_external',
        'methodname'    => 'save_log_entry',
        'classpath'     => 'mod/hypercast/db/external.php',
        'description'   => 'Save LogEntries',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true
    ),
    
    
    'mod_hypercast_getEnrolledUser' => array(
        'classname'     => 'mod_hypercast_external',
        'methodname'    => 'getEnrolledUser',
        'classpath'     => 'mod/hypercast/db/external.php',
        'description'   => 'Save LogEntries',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true
    ),
    
    
);
