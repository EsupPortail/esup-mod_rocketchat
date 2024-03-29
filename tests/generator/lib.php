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
 * mod_bigbluebuttonbn data generator
 *
 * @package    mod_bigbluebuttonbn
 * @category   test
 * @copyright  2018 - present, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

/**
 * rocketchat module data generator
 *
 * @package    mod_rocketchat
 * @category   test
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Céline Pervès <cperves@unistra.fr>
 */
class mod_rocketchat_generator extends testing_module_generator {

    /**
     * Creates an instance of rocket.chat for testing purposes.
     *
     * @param array|stdClass $record data for module being generated.
     * @param null|array $options general options for course module.
     * @return stdClass record from module-defined table with additional field cmid
     */
    public function create_instance($record = null, array $options = null) {
        global $DB;
        $groupname = $record['groupname'];
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $editingteacher = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $now = time();
        $defaults = array(
            "rocketchatid" => sha1(rand()),
            "rocketchatname" => $groupname,
            "displaytype" => 1,
            "embedded" => 1,
            "popupheight" => 700,
            "popupwidth" => 700,
            "moderatorroles" => "$editingteacher->id",
            "userroles" => "$student->id",
            "timecreated" => $now,
            "timemodified" => $now,
            "presentation" => null
        );
        $record = (array)$record;
        foreach ($defaults as $key => $value) {
            if (!isset($record[$key])) {
                $record[$key] = $value;
            }
        }
        return parent::create_instance((object)$record, (array)$options);
    }
}
