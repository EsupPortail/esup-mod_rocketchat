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
 * observers file
 * @package     mod_rocketchat
 * @category    event
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Céline Pervès <cperves@unistra.fr>
 */

namespace mod_rocketchat;

defined('MOODLE_INTERNAL') || die();

class observers {
    public static function course_deleted(\core\event\course_deleted $event) {

    }

    public static function user_enrolment_created(\core\event\course_deleted $event) {

    }

    public static function user_enrolment_deleted(\core\event\course_deleted $event) {

    }

    public static function module_updated(\core\event\course_deleted $event) {

    }

    public static function module_deleted(\core\event\course_deleted $event) {

    }

    public static function user_deleted(\core\event\course_deleted $event) {

    }

}