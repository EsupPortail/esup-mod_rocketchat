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
 * Folder plugin version information
 *
 * @package
 * @subpackage
 * @copyright  2021 unistra  {@link http://unistra.fr}
 * @author Matthieu Fuchs <matfuchs@unistra.fr>, Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
 */
namespace mod\rocketchat\task;

defined('MOODLE_INTERNAL') || die();

use mod_rocketchat_tools;
class sync_all_rooms_task extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('sync_all_rooms', 'mod_rocketchat');
    }

    public function execute() {
        global $DB;
        $rocketchatcourses = $DB->get_record('rocketchat', []);
        foreach ( $rocketchatcourses as $course) {
            mod_rocketchat_tools::synchronize_group_members_for_course($course->id);
        }
    }
}
