<?php

use core_user\table\participants_search;
use mod_rocketchat\api\manager\rocket_chat_api_manager;

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
$PAGE->set_pagelayout('admin');

$rocketid = required_param('rocketchat_id', PARAM_RAW_TRIMMED);
$courseid = required_param('course_id', PARAM_RAW_TRIMMED);
$conditions['rocketchatid'] = $rocketid;
echo $OUTPUT->header();
$details = $DB->get_records('rocketchat', $conditions);
$details =array_values($details);
echo $OUTPUT->heading(get_string('header_details', 'mod_rocketchat'). $details[0]->name);

$config = get_config('mod_rocketchat');
$instanceurl = $config->instanceurl;
$restapiroot  = $config->restapiroot;
$apiuser  = $config->apiuser;
$apitoken  = $config->apitoken;
$rocketchatapimanager = new rocket_chat_api_manager();
$channel = $rocketchatapimanager->get_rocketchat_room_object($rocketid);

$coursecontext = context_course::instance($courseid);
$moodlemembers = get_enrolled_users($coursecontext);
$rocketchatmoduleinstance = $DB->get_record('rocketchat', array('rocketchatid' => $rocketid));
if (!$rocketchatmoduleinstance) {
    print_error("can't load rocketchat instance $courseid in moodle");
}

$moderatorroles = array_filter(explode(',', $rocketchatmoduleinstance->moderatorroles));
$userroles = $rocketchatmoduleinstance->userroles;

$count =0;
foreach ($moodlemembers as $moodleuser){
    $count++;
    $moodleUsers[]['name'] = $moodleuser->firstname.' '.$moodleuser->lastname;
    //var_dump($moodleuser->username. ' '.mod_rocketchat_tools::has_rocket_chat_moderator_role($moderatorroles, $moodleuser, $coursecontext));
}
//self::has_rocket_chat_user_role($userroleids, $moodleuser, $coursecontext);

$group = $rocketchatapimanager->get_rocketchat_group_object($rocketid);
$rocketUsers = $group->members();
var_dump($rocketUsers);

$details[0]->intro = $count;
$result = $channel->info();
$data = [
    'moodle' => $details[0],
    'moodleUsers' => $moodleUsers,
    'rocketchat' => $result,
    'rocketchatUsers' => $rocketUsers
];

echo $OUTPUT->render_from_template('mod_rocketchat/details', $data);

echo $OUTPUT->footer();