<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
use core_user\table\participants_search;
use mod_rocketchat\api\manager\rocket_chat_api_manager;
use mod_rocketchat_tools;


require('../../config.php');
require_once('locallib.php');
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login(null, false);
$courseid = required_param('course_id', PARAM_RAW_TRIMMED);
$rocketid = required_param('rocketchat_id', PARAM_RAW_TRIMMED);
$moduleid = required_param('module_id', PARAM_RAW_TRIMMED);
$sync = optional_param('sync',  '0', PARAM_RAW_TRIMMED);
$recreate = optional_param('recreate',  '0', PARAM_RAW_TRIMMED);

admin_externalpage_setup('mod_rocketchat_admin_interface', '', array(),
    new moodle_url('/mod/rocketchat/rocket_room_details.php',
        array('course_id' => $courseid, 'rocketchat_id' => $rocketid, 'module_id' => $moduleid)));

$PAGE->navbar->add(get_string('pluginname_admin', 'mod_rocketchat'));
$PAGE->requires->jquery();
$PAGE->set_context(context_system::instance());
$PAGE->requires->css(new moodle_url('/mod/rocketchat/styles.css'));
$PAGE->set_pagelayout('admin');

$course = $DB->get_record('course', array('id' => $courseid));
if ($sync == 1) {
    mod_rocketchat_tools::synchronize_group_members_for_course($courseid);
}
$conditions['id'] = $moduleid;
$details = $DB->get_record('rocketchat', $conditions);
echo $OUTPUT->heading(get_string('header_details', 'mod_rocketchat'). $details->name);

echo $OUTPUT->header();

$config = get_config('mod_rocketchat');
$instanceurl = $config->instanceurl;
$restapiroot  = $config->restapiroot;
$apiuser  = $config->apiuser;
$apitoken  = $config->apitoken;
$rocketchatapimanager = new rocket_chat_api_manager();



if ($recreate == 1) {
    global $CFG;
    $rocketid = mod_rocketchat_tools::create_rocketchat_room($moduleid, $course, $rocketchatapimanager);
    $details->rocketchatid = $rocketid;
}
$coursecontext = context_course::instance($courseid);
$moodlemembers = get_enrolled_users($coursecontext);
$count = 0;
foreach ($moodlemembers as $moodleuser) {
    $count++;
    $moodleusers[]['name'] = $moodleuser->firstname.' '.$moodleuser->lastname;
}
$details->intro = $count;
try {
    $channel = $rocketchatapimanager->get_rocketchat_room_object($rocketid);
    $result = $channel->info();

    $group = $rocketchatapimanager->get_rocketchat_group_object($rocketid);
    $rocketusers = $group->members();
} catch (Exception $e) {
    $result = 0;
    $rocketusers = 0;
}

$syncbutton = new moodle_url('/mod/rocketchat/rocket_room_details.php',
    ['rocketchat_id' => $rocketid,
        'module_id' => $moduleid,
        'course_id' => $courseid,
        'sync' => '1',
        'sesskey' => sesskey()]);
if ($rocketid == 0) {
    $recreate = new moodle_url('/mod/rocketchat/rocket_room_details.php',
        ['rocketchat_id' => 0,
            'module_id' => $moduleid,
            'course_id' => $courseid,
            'recreate' => '1',
            'sesskey' => sesskey()]);

}
$data = [
    'moodle' => $details,
    'moodleUsers' => $moodleusers,
    'rocketchat' => $result,
    'rocketchatUsers' => $rocketusers,
    'syncbutton' => $syncbutton,
    'recreatebutton' => $recreate
];
echo $OUTPUT->render_from_template('mod_rocketchat/details', $data);

echo $OUTPUT->footer();
