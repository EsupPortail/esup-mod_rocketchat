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
 * The main mod_rocketchat configuration form.
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_rocketchat
 * @author
 * @copyright  2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_rocketchat_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        //TODO add capability for creating RocketChat room
        // General Section.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding a name field not the channel name but the name
        $mform->addElement('text', 'name', get_string('name', 'mod_rocketchat'), array('size' => '255'));

        // Strip name if necessary
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $options = mod_rocketchat_tools::get_display_options();

        $mform->addElement('select', 'displaytype', get_string('displaytype', 'mod_rocketchat'),
            $options);

        $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'mod_rocketchat'));
        $mform->setType('popupwidth', PARAM_INT);
        $mform->setDefault('popupwidth', 700);
        if (count($options) > 1) {
            $mform->disabledIf('popupwidth', 'displaytype',
                'noteq', mod_rocketchat_tools::DISPLAY_POPUP);
        }

        $mform->addElement('text', 'popupheight', get_string('popupheight', 'mod_rocketchat'));
        $mform->setType('popupheight', PARAM_INT);
        $mform->setDefault('popupheight', 700);
        if (count($options) > 1) {
            $mform->disabledIf('popupheight', 'displaytype',
                'noteq', mod_rocketchat_tools::DISPLAY_POPUP);
        }

        $rolesoptions = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT, true);
        $moderatorroles = $mform->addElement('select', 'moderatorroles',
            get_string('moderatorroles', 'mod_rocketchat'),
            $rolesoptions);
        $moderatorroles->setMultiple(true);
        $mform->setDefault('moderatorroles',get_config('mod_rocketchat','defaultmoderatorroles'));

        $userroles = $mform->addElement('select', 'userroles',
            get_string('userroles', 'mod_rocketchat'),
            $rolesoptions);
        $userroles->setMultiple(true);

        $mform->setDefault('userroles',get_config('mod_rocketchat','defaultuserroles'));
        if(!has_capability('mod/rocketchat:candefineroles', $this->get_context())) {
            $moderatorroles->setAttributes(array('disabled' => 'true'));
            $userroles->setAttributes(array('disabled' => 'true'));
        }

        // Do not add availibility at the moment.
        /*
        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);
         */

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }



}
