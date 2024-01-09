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
 * Prints an instance of mod_rocketchat.
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/classes/api/manager/rocket_chat_api_manager.php');

// Course_module ID.
$id = optional_param('id', 0, PARAM_INT);
// Mmodule instance id.
$n  = optional_param('r', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('rocketchat', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('rocketchat', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $moduleinstance = $DB->get_record('rocketchat', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('rocketchat', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingparam');
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_rocketchat\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('rocketchat', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/rocketchat/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->add_body_class('limitedwidth');

$config = get_config('mod_rocketchat');
echo $OUTPUT->header();
$rocketchatapiconfig = new \mod_rocketchat\api\manager\rocket_chat_api_config();
$embbeded = $moduleinstance->embbeded;
$link = mod_rocketchat_tools::get_group_link($moduleinstance->rocketchatid, $embbeded);
echo html_writer::start_div('container-fluid tertiary-navigation');
echo html_writer::start_div('row');
echo html_writer::start_div('navitem');
switch ($moduleinstance->displaytype) {
    case mod_rocketchat_tools::DISPLAY_POPUP:
        echo $OUTPUT->action_link(
            $link,
            get_string('joinrocketchat', 'mod_rocketchat'),
            new popup_action(
                'click',
                $link,
                'joinrocketchat',
                array('height' => $moduleinstance->popupheight, 'width' => $moduleinstance->popupwidth),
            ),
            array('class' => 'btn btn-secondary')
        );
        break;
    case mod_rocketchat_tools::DISPLAY_CURRENT:
        echo $OUTPUT->action_link(
            $link,
            get_string('joinrocketchat', 'mod_rocketchat'),
            null,
            array('class' => 'btn btn-secondary')
        );
        break;
    default:
        // DISPLAY_NEW and default case.
        echo html_writer::link(
            $link,
            get_string('joinrocketchat', 'mod_rocketchat'),
            array('onclick' => 'this.target="_blank";', 'class' => 'btn btn-secondary')
        );
        break;
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
