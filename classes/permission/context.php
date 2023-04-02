<?php

/**
 *
 * @author  2021 Marc Burchart <marc.burchart@tu-dortmund.de> , Kooperative Systeme, FernUniversität Hagen
 *
 */

namespace mod_hypercast\permission;

defined('MOODLE_INTERNAL') || die();


class context {

    protected $_context;
    protected $_userid;
    protected $_roles;

    function __construct($userid, $context){
        require_login();
        $this->_context = $context;
        $this->_userid = $userid;
        $this->_roles = get_user_roles($this->_context, $this->_userid);
    }

    public function isSiteAdmin(){
        return is_siteadmin($this->_userid);
    }

    public function isManager(){
        return $this->findRole('manager');
    }

    public function isCourseCreator(){
        return $this->findRole('coursecreator');
    }

    public function isEditingTeacher(){
        return $this->findRole('editingteacher');
    }

    public function isTeacher(){
        return $this->findRole('teacher');
    }

    public function isStudent(){
        return $this->findRole('student');
    }

    public function isGuest(){
        return is_guest($this->_context, $this->_userid);
    }

    public function isUser(){
        return $this->findRole('user');
    }

    public function isFrontPage(){
        return $this->findRole('frontpage');
    }

    public function findRole($shortname){
        foreach($this->_roles as $role){
            if(isset($role->shortname) && strtolower($role->shortname) === strtolower($shortname)){
                return true;
            }
        }
        return false;
    }

    public function hasViewCapability(){
        return is_viewing($this->_context, $this->_userid);
    }

    public function isEnrolled(){
        return is_enrolled($this->_context, $this->_userid);
    }

    public function isAnyKindOfModerator(): bool {
        #return false;
        if(
            $this->isSiteAdmin() ||
            $this->isManager() ||
            $this->isCourseCreator() ||
            $this->isEditingTeacher() ||
            $this->isTeacher()
        ){
            return true;
        }
        return false;
    }

    public function getContext(){
        return $this->_context;
    }

    public function getCourseContext(){
        $context = $this->_context;
        return $context->get_course_context();
    }
}

?>
