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
 * The task that provides a complete restore of mod_rocketchat is defined here.
 *
 * @package     mod_rocketchat
 * @category    restore
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'//mod/rocketchat/backup/moodle2/restore_rocketchat_stepslib.php');

/**
 * Restore task for mod_rocketchat.
 */
class restore_rocketchat_activity_task extends restore_activity_task {

    /**
     * Defines particular settings that this activity can have.
     */
    protected function define_my_settings() {
        return;
    }

    /**
     * Defines particular steps that this activity can have.
     *
     * @return base_step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_rocketchat_activity_structure_step('rocketchat_structure', 'rocketchat.xml'));
    }

    /**
     * Defines the contents in the activity that must be processed by the link decoder.
     *
     * @return array.
     */
    public static function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('rocketchat', array('intro'), 'rocketchat.xml');

        return $contents;
    }

    /**
     * Defines the decoding rules for links belonging to the activity to be executed by the link decoder.
     *
     * @return array.
     */
    public static function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('ROCKETCHATVIEWBYID', '/mod/rocketchat/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('ROCKETCHATINDEX', '/mod/rocketchat/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Defines the restore log rules that will be applied by the
     * {@link restore_logs_processor} when restoring mod_rocketchat logs. It
     * must return one array of {@link restore_log_rule} objects.
     *
     * @return array.
     */
    public static function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('rocketchat', 'add', 'view.php?id={course_module}', '{rocketchat}');
        $rules[] = new restore_log_rule('rocketchat', 'update', 'view.php?id={course_module}', '{rocketchat}');
        $rules[] = new restore_log_rule('rocketchat', 'view', 'view.php?id={course_module}', '{rocketchat}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    public static function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('rocketchat', 'view all', 'index.php?id={course}', null);

        return $rules;
    }

    public function get_plan_mode() {
        return $this->plan->get_mode();
    }
}
