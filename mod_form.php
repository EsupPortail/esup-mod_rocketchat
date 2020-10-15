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
 * @author Céline Pervès<cperves@unistra.fr>
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
        // General Section.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding a name field not the channel name but the name.
        $mform->addElement('text', 'name', get_string('name', 'mod_rocketchat'), array('size' => '255'));

        // Strip name if necessary.
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $embbeddisplaymodechange = has_capability('mod/rocketchat:change_embedded_display_mode', $this->get_context());
        if ($embbeddisplaymodechange) {
            $mform->addElement('checkbox', 'embbeded',
                get_string('embedded_display_mode', 'mod_rocketchat'),
                get_string('embedded_display_mode_desc', 'mod_rocketchat'));
        } else {
            $mform->addElement('hidden', 'embbeded');
        }
        $mform->setDefault('embbeded', get_config('mod_rocketchat', 'embedded_display_mode'));

        $options = mod_rocketchat_tools::get_display_options();

        $mform->addElement('select', 'displaytype', get_string('displaytype', 'mod_rocketchat'),
            $options);

        $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'mod_rocketchat'));
        $mform->setType('popupwidth', PARAM_INT);
        $mform->setDefault('popupwidth', 700);
        if (count($options) > 1) {
            $mform->disabledif ('popupwidth', 'displaytype',
                'noteq', mod_rocketchat_tools::DISPLAY_POPUP);
        }

        $mform->addElement('text', 'popupheight', get_string('popupheight', 'mod_rocketchat'));
        $mform->setType('popupheight', PARAM_INT);
        $mform->setDefault('popupheight', 700);
        if (count($options) > 1) {
            $mform->disabledif ('popupheight', 'displaytype',
                'noteq', mod_rocketchat_tools::DISPLAY_POPUP);
        }

        $rolesreadonly = !has_capability('mod/rocketchat:candefineroles', $this->get_context());
        $rolesoptions = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT, true);

        $moderatorroles = $mform->addElement('select', 'moderatorroles'.($rolesreadonly ? 'ro' : ''),
            get_string('moderatorroles', 'mod_rocketchat'),
            $rolesoptions);
        $moderatorroles->setMultiple(true);

        $userroles = $mform->addElement('select', 'userroles'.($rolesreadonly ? 'ro' : ''),
            get_string('userroles', 'mod_rocketchat'),
            $rolesoptions);
        $userroles->setMultiple(true);

        if ($rolesreadonly) {
            $moderatorroles->setAttributes(array('disabled' => 'true'));
            $userroles->setAttributes(array('disabled' => 'true'));
            $mform->addElement('hidden', 'moderatorroles');
            $mform->addElement('hidden', 'userroles');
        }
        $mform->setDefault('moderatorroles', get_config('mod_rocketchat', 'defaultmoderatorroles'));
        $mform->setDefault('userroles', get_config('mod_rocketchat', 'defaultuserroles'));
        $mform->setType('moderatorroles', PARAM_RAW);
        $mform->setType('userroles', PARAM_RAW);

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    public function data_postprocessing($data) {
        $data->moderatorroles = is_array($data->moderatorroles) ? implode(',', $data->moderatorroles) : $data->moderatorroles;
        $data->userroles = is_array($data->userroles) ? implode(',', $data->userroles) : $data->userroles;
        // Funtion get data return null when checkbox is not checked.
        $data->embbeded = !property_exists($data, 'embbeded') ? 0 : $data->embbeded;
    }


}
