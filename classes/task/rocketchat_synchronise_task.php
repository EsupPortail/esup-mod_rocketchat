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
 * @author Matthieu Fuchs <cperves@unistra.fr>
 * @author Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
 */
namespace mod_rocketchat\task;
require_once("$CFG->dirroot/mod/rocketchat/locallib.php");

use mod_rocketchat_tools;

defined('MOODLE_INTERNAL') || die();

class rocketchat_synchronise_task extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('rocketchat_synchronise_task', 'mod_rocketchat');
    }

    public function execute() {
        global $DB;
        $courses = $DB->get_record_sql('select distinct course from {rocketchat}');
        foreach ($courses as $course) {
            mod_rocketchat_tools::synchronize_group_members_for_course($course);
        }
    }
}
