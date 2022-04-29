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
 * Backup steps for mod_rocketchat are defined here.
 *
 * @package     mod_rocketchat
 * @category    backup
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete structure for backup, with file and id annotations.
 */
class backup_rocketchat_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        $rocketchat = new backup_nested_element('rocketchat', array('id'),
            array('name', 'intro', 'introformat', 'timecreated', 'timemodified', 'rocketchatid',
                'displaytype', 'popupheight', 'popupwith', 'embedded', 'moderatorroles', 'userroles'));
        $rocketchat->set_source_table('rocketchat', array('id' => backup::VAR_ACTIVITYID));
        $rocketchat->annotate_files('mod_rocketchat', 'intro', null);
        return $this->prepare_activity_structure($rocketchat);
    }
}
