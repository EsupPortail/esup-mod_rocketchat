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
        global $CFG, $DB;
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

        $mform->addElement('header', 'displaysection',
            get_string('displaysection', 'mod_rocketchat'));
        $mform->setExpanded('displaysection');
        $embbeddisplaymodechange = has_capability('mod/rocketchat:change_embedded_display_mode', $this->get_context());
        if ($embbeddisplaymodechange) {
            $mform->addElement('checkbox', 'embbeded',
                get_string('embedded_display_mode', 'mod_rocketchat'),
                get_string('embedded_display_mode_desc', 'mod_rocketchat'));
        } else {
            $mform->addElement('hidden', 'embbeded');
            $mform->setType('embbeded', PARAM_INT);
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

        $mform->addElement('header', 'rolessection',
            get_string('rolessection', 'mod_rocketchat'));
        $mform->setExpanded('rolessection');
        $rolesoptions = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT, true);
        $defaultmoderatorroles = get_config('mod_rocketchat', 'defaultmoderatorroles');
        $defaultuserroles = get_config('mod_rocketchat', 'defaultuserroles');
        $rolesreadonly = !has_capability('mod/rocketchat:candefineroles', $this->get_context());
        $moderatorroletext = '';
        $userroletext = '';
        if ($rolesreadonly) {
            if (!empty($this->_instance)) {
                $moderatorroletext = $this->format_roles($this->get_current()->moderatorroles, $rolesoptions);
                $userroletext = $this->format_roles($this->get_current()->userroles, $rolesoptions);
            } else {
                $moderatorroletext = $this->format_roles(get_config('mod_rocketchat', 'defaultmoderatorroles'), $rolesoptions);
                $userroletext = $this->format_roles(get_config('mod_rocketchat', 'defaultuserroles'), $rolesoptions);
            }
            $mform->addElement('static', 'moderatorrolesstatic',
                get_string('moderatorroles', 'mod_rocketchat'), $moderatorroletext);
            $mform->addElement('hidden', 'moderatorroles');
            $mform->addElement('static', 'userrolesstatic',
                get_string('userroles', 'mod_rocketchat'), $userroletext);
            $mform->addElement('hidden', 'userroles');
        } else {
            $moderatorroles = $mform->addElement('select', 'moderatorroles',
                get_string('moderatorroles', 'mod_rocketchat'),
                $rolesoptions);
            $moderatorroles->setMultiple(true);

            $userroles = $mform->addElement('select', 'userroles',
                get_string('userroles', 'mod_rocketchat'),
                $rolesoptions);
            $userroles->setMultiple(true);
        }
        $mform->setType('moderatorroles', PARAM_RAW);
        $mform->setType('userroles', PARAM_RAW);
        $mform->setDefault('moderatorroles', get_config('mod_rocketchat', 'defaultmoderatorroles'));
        $mform->setDefault('userroles', get_config('mod_rocketchat', 'defaultuserroles'));

        if ((boolean)get_config('mod_rocketchat', 'retentionfeature')) {
            if (has_capability('mod/rocketchat:canactivateretentionpolicy', $this->get_context())) {
                $mform->addElement('header', 'retentionsection',
                    get_string('retentionsection', 'mod_rocketchat'));
                $mform->setExpanded('retentionsection');
                $mform->addElement('checkbox', 'retentionenabled',
                    get_string('retentionenabled', 'mod_rocketchat'),
                    get_string('retentionenabled_desc', 'mod_rocketchat')
                );
                // Parameter retentionenabled means Rocket.Chat retentionEnabled + overrideGlobal.
                $mform->setDefault('retentionenabled', get_config('mod_rocketchat', 'retentionenabled'));
                $mform->addElement('text', 'maxage', get_string('maxage', 'mod_rocketchat'));
                $mform->setType('maxage', PARAM_INT);
                $mform->disabledif('maxage', 'retentionenabled',
                    'notchecked');

                if (has_capability('mod/rocketchat:candefineadvancedretentionparamaters', $this->get_context())) {
                    $mform->addElement('checkbox', 'filesonly',
                        get_string('filesonly', 'mod_rocketchat'),
                        get_string('filesonly_desc', 'mod_rocketchat')
                    );
                    $mform->disabledif('filesonly', 'retentionenabled',
                        'notchecked');
                    $mform->addElement('checkbox', 'excludepinned',
                        get_string('excludepinned', 'mod_rocketchat'),
                        get_string('excludepinned_desc', 'mod_rocketchat')
                    );
                    $mform->disabledif('excludepinned', 'retentionenabled',
                        'notchecked');
                } else {
                    $mform->addElement('hidden', 'filesonly');
                    $mform->setType('filesonly', PARAM_INT);
                    $mform->addElement('hidden', 'excludepinned');
                    $mform->setType('excludepinned', PARAM_INT);
                }
            } else {
                $mform->addElement('hidden', 'retentionenabled');
                $mform->setType('retentionenabled', PARAM_INT);
                $mform->setDefault('retentionenabled', get_config('mod_rocketchat', 'retentionenabled'));
                $mform->addElement('hidden', 'maxage');
                $mform->setType('maxage', PARAM_INT);
                $mform->addElement('hidden', 'filesonly');
                $mform->setType('filesonly', PARAM_INT);
                $mform->addElement('hidden', 'excludepinned');
                $mform->setType('excludepinned', PARAM_INT);
            }

            $defaultmaxage = get_config('mod_rocketchat', 'maxage');
            $defaultmaxage = intval($defaultmaxage);
            $mform->setDefault('maxage', $defaultmaxage);
            $mform->setDefault('filesonly', get_config('mod_rocketchat', 'excludepinned'));
            $mform->setDefault('excludepinned', get_config('mod_rocketchat', 'excludepinned'));
        }

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

    /**
     * @param string $formattedrole
     * @param array $rolesoptions
     */
    protected function format_roles($roleids, $rolesoptions) {
        $i = 1;
        $formattedrole = '';
        foreach (array_filter(explode(',', $roleids)) as $moderatorroleid) {
            if ($i > 1) {
                $formattedrole .= ',';
            }
            $formattedrole .= $rolesoptions[$moderatorroleid];
            $i++;
        }
        return $formattedrole;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (array_key_exists('retentionenabled', $data) && $data['retentionenabled'] == 1 ) {
            $maxagelimit = get_config('mod_rocketchat', 'maxage_limit');
            if ($data['maxage'] > $maxagelimit) {
                $errors['maxage'] = get_string('limit_override', 'mod_rocketchat', $maxagelimit);
            }
        }
        return $errors;
    }

}
