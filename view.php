<?php

##
#   Please do not change!
##

require('../../config.php');
require_once('lib.php');

$pdata = strtolower(basename(dirname(__DIR__)).'_'.basename(__DIR__)).'\core\pinfo';
$pdata = new $pdata();
$pdata = $pdata->getData();

// Change view.php?id=19 to view.php/19/[...]
$cmid = optional_param('id', 0, PARAM_INT);
if ($cmid > 0) {
    $path = "{$pdata->moodlePath}/view.php/{$cmid}/";
    redirect(new \moodle_url($path));
}

// Get the coursemoduleid
try{
    $paths = explode('/', $_SERVER['REQUEST_URI']);
    $baseindex = array_search('view.php', $paths);
    if (count($paths) > $baseindex + 1) {
        $cmid = intval($paths[$baseindex + 1]);
    }
    if(!isset($cmid) || !is_int($cmid) || $cmid <= 0){
        throw new \Exception(
            get_string('invcmid', $pdata->fullName)
        );
    }
    list($course, $coursemodule) = get_course_and_cm_from_cmid($cmid, $pdata->name);
} catch(Exception $ex){
    throw new \Exception(
        get_string('invcmid', $pdata->fullName)
    );
}

$url = new moodle_url(
    "{$pdata->moodlePath}/view.php", ['id' => $cmid]
);

// Require Login
require_login($course, true, $coursemodule);

$config = get_config($pdata->name);

##
#   From here on changes can be made!
##

$permission = $pdata->fullName.'\permission\course';
$permission = new $permission($USER->id, $course->id);

$title = get_string('modulename', $pdata->fullName);

$PAGE->set_context($coursemodule->context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($url);
$PAGE->set_pagelayout('mydashboard');

$PAGE->requires->js_call_amd($pdata->fullName.'/app-lazy', 'init', [
    'coursemoduleid' => $coursemodule->id,
    'contextid' => $coursemodule->context->id,
    'fullPluginName' => $pdata->fullName,
    'isModerator' => $permission->isAnyKindOfModerator(),
    'userid' => $USER->id,
]);

echo $OUTPUT->header();

echo $OUTPUT->footer($course);
