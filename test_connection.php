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

/**
 *
 * Enable Rocket.Chat connection test
 *
 * @package    mod
 * @subpackage rocket.chat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
    require_once('./config-test.php');
}
use \mod_rocketchat\api\manager\rocket_chat_api_manager;
require_login();

require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('rocketchatconnectiontest');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/mod/rocketchat/test_rocketchat_connection.php'));
$PAGE->set_title(get_string('testconnection', 'mod_rocketchat'));
$PAGE->set_heading(get_string('testconnection', 'mod_rocketchat'));
$PAGE->set_pagelayout('admin');

$site = get_site();

$config = get_config('mod_rocketchat');
$instanceurl = $config->instanceurl;
$restapiroot  = $config->restapiroot;
$apiuser  = $config->apiuser;
$apitoken  = $config->apitoken;


echo $OUTPUT->header();
echo $OUTPUT->container_start('center');

$result = true;
try {
    $rocketchatapimanager = new rocket_chat_api_manager();
    $result = $rocketchatapimanager->get_adminuser_info();
} catch (Exception $e) {
    $result = false;
    echo html_writer::tag('h2', get_string('errorintestwhilegconnection', 'mod_rocketchat'));
    echo html_writer::div(get_string('testerrorcode', 'mod_rocketchat', $e->getCode()), 'error');
    echo html_writer::div(get_string('testerrormessage', 'mod_rocketchat', $e->getMessage()), 'error');
}
if ($result) {
    echo html_writer::tag('h2', get_string('connectiontestresult', 'mod_rocketchat'));
    echo html_writer::div(get_string('connection-success', 'mod_rocketchat'), 'alert');
}
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
