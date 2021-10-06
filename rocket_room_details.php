<?php

use core_user\table\participants_search;
use mod_rocketchat\api\manager\rocket_chat_api_manager;
use mod_rocketchat_tools;

global $PAGE, $CFG, $DB;

require('../../config.php');
require_once('locallib.php');
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');


require_login(null, false);
admin_externalpage_setup('mod_rocketchat_admin_interface', '', array(),
    new moodle_url('/mod/rocketchat/rocket_room_details.php', array()));
$PAGE->navbar->add(get_string('pluginname_admin', 'mod_rocketchat'));
$PAGE->requires->jquery();
$PAGE->set_context(context_system::instance());
$PAGE->requires->css(new moodle_url('/mod/rocketchat/styles.css'));
$PAGE->set_pagelayout('admin');

$courseid = required_param('course_id', PARAM_RAW_TRIMMED);
$rocketid = required_param('rocketchat_id', PARAM_RAW_TRIMMED);
$course = $DB->get_record('course', array('id' => $courseid));
$moduleid = required_param('module_id', PARAM_RAW_TRIMMED);
$sync = optional_param('sync', '0',PARAM_RAW_TRIMMED);
$recreate = optional_param('recreate', '0',PARAM_RAW_TRIMMED);
if($sync == 1){
    global $CFG;
    mod_rocketchat_tools::synchronize_group_members_for_course($courseid);
}
$conditions['id'] = $moduleid;
$details = $DB->get_records('rocketchat', $conditions);
$details =array_values($details);
echo $OUTPUT->heading(get_string('header_details', 'mod_rocketchat'). $details[0]->name);

echo $OUTPUT->header();

$config = get_config('mod_rocketchat');
$instanceurl = $config->instanceurl;
$restapiroot  = $config->restapiroot;
$apiuser  = $config->apiuser;
$apitoken  = $config->apitoken;
$rocketchatapimanager = new rocket_chat_api_manager();

if($recreate == 1){
    global $CFG;
    $groupname = mod_rocketchat_tools::rocketchat_group_name($moduleid, $course);
    $groupid = $rocketchatapimanager->create_rocketchat_group($groupname);
    $rocketchat = $DB->get_record('rocketchat', array('id' => $moduleid));
    $rocketchat->rocketchatid = $groupid;
    $details[0]->rocketchatid = $groupid;
    $rocketid = $groupid;
    $DB->update_record('rocketchat', $rocketchat);
    // Need to enrol users.
    // Course information to fit ton function needs.
    $rocketchat->course = $course->id;
    mod_rocketchat_tools::enrol_all_concerned_users_to_rocketchat_group($rocketchat,
        get_config('mod_rocketchat', 'background_restore'));

    if ((boolean)get_config('mod_rocketchat', 'retentionfeature')) {
        $retentionsettings = array(
            'retentionenabled' =>
                property_exists($rocketchat, 'retentionenabled') ? $rocketchat->retentionenabled : false,
            'maxage' => $rocketchat->maxage,
            'filesonly' => property_exists($rocketchat, 'filesonly') ? $rocketchat->filesonly : false,
            'excludepinned' => property_exists($rocketchat, 'excludepinned') ? $rocketchat->excludepinned : false
        );
        $rocketchatapimanager->save_rocketchat_group_settings($rocketchat->rocketchatid, $retentionsettings);
    }
}
$coursecontext = context_course::instance($courseid);
$moodlemembers = get_enrolled_users($coursecontext);
$count =0;
foreach ($moodlemembers as $moodleuser){
    $count++;
    $moodleUsers[]['name'] = $moodleuser->firstname.' '.$moodleuser->lastname;
    //var_dump($moodleuser->username. ' '.mod_rocketchat_tools::has_rocket_chat_moderator_role($moderatorroles, $moodleuser, $coursecontext));
}
$details[0]->intro = $count;
//self::has_rocket_chat_user_role($userroleids, $moodleuser, $coursecontext);
try{
    $channel = $rocketchatapimanager->get_rocketchat_room_object($rocketid);
    $result = $channel->info();

    $group = $rocketchatapimanager->get_rocketchat_group_object($rocketid);
    $rocketUsers = $group->members();
} catch (Exception $e){
    $result = 0;
    $rocketUsers = 0;
}

$sync_button = new moodle_url('/mod/rocketchat/rocket_room_details.php',['rocketchat_id' => $rocketid,'module_id' => $moduleid,'course_id' => $courseid, 'sync' => '1', 'sesskey' => sesskey()]);
if($rocketid == 0)
$recreate = new moodle_url('/mod/rocketchat/rocket_room_details.php',['rocketchat_id' =>  0,'module_id' => $moduleid,'course_id' => $courseid, 'recreate' => '1', 'sesskey' => sesskey()]);
$data = [
    'moodle' => $details[0],
    'moodleUsers' => $moodleUsers,
    'rocketchat' => $result,
    'rocketchatUsers' => $rocketUsers,
    'syncbutton' => $sync_button,
    'recreatebutton' => $recreate
];
echo $OUTPUT->render_from_template('mod_rocketchat/details', $data);

echo $OUTPUT->footer();