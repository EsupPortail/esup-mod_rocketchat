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
 * This file contains the definition for course table which subclassses easy_table
 *
 * @package   tool_legacyfilesmigration
 * @copyright  2021 unistra  {@link http://unistra.fr}
 * @author Celine Perves <cperves@unistra.fr>
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

class rocketchat_admin_table_recycle extends table_sql implements renderable {
    /** @var int $perpage */
    private $perpage = 10;
    /** @var int $rownum (global index of current row in table) */
    private $rownum = -1;
    /** @var renderer_base for getting output */
    private $output = null;
    /** @var boolean $any - True if there is one or more entries*/
    public $anyentry = false;

    /**
     * This table loads the list of all course programmed to be restored from external p)lf to current plf
     *
     * @param int $perpage How many per page
     * @param int $rowoffset The starting row for pagination
     */
    public function __construct($perpage=null, $page=null, $rowoffset=0) {
        global $PAGE, $CFG, $DB;
        parent::__construct('tool_my_external_backup_restore_course_admin_entries');
        if (isset($perpage)) {
            $this->perpage = $perpage;
        }
        if (isset($page)) {
            $this->currpage = $page;
        }

        $this->define_baseurl(new moodle_url('/'.$CFG->admin.'/mod/rocketchat/management.php'));

        $this->anyentries = $DB->get_records('rocketchatxrecyclebin');

        if ($rowoffset) {
            $this->rownum = $rowoffset - 1;
        }
        $params = array('component' => 'course', 'filearea' => 'legacy', 'coursecontext' => CONTEXT_COURSE);
        $fields = '*';
        $from = '{rocketchatxrecyclebin}';
        $where = 'true';

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql('select count(*) from {rocketchatxrecyclebin}');

        $columns = array();
        $headers = array();

        $columns[] = $headers[] = 'id';
        $columns[] = $headers[] = 'binid';
        $columns[] = $headers[] = 'rocketchatid';

        // ...set the columns
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(true, 'course');
        $this->use_pages = true;
        $this->collapsible(false);
    }

    /**
     * Return the number of rows to display on a single page
     *
     * @return int The number of rows per page
     */
    public function get_rows_per_page() {
        return $this->perpage;
    }

    /**
     * Format a link to the assignment instance
     *
     * @param stdClass $row
     * @return string
     */
    public function col_externalcoursename(stdClass $row) {
        return html_writer::link(new moodle_url($row->externalmoodleurl.'/course/view.php',
            array('id' => $row->externalcourseid)), $row->externalcoursename);
    }


    public function col_courseid(stdClass $row) {
        if ($row->courseid) {
            return html_writer::link(new moodle_url('/course/view.php',
                array('id' => $row->courseid)), $row->courseid);

        } else {
            return '';
        }
    }


    /**
     * internal category input field
     *
     * @param stdClass $row
     * @return string
     */
    public function col_internalcategory(stdClass $row) {
        return html_writer::empty_tag('input',
            array('name' => 'internalcategory_'.$row->id,
                'type' => 'text',
                'value' => $row->internalcategory,
                'size' => "4"));

    }

    /**
     * status selection
     * @param stdClass $row
     * @return string
     */
    public function col_status(stdClass $row) {
        return html_writer::select(
            array(
                block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED =>
                    get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_SCHEDULED,
                        'block_my_external_backup_restore_courses'),
            block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS =>
                get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_INPROGRESS,
                    'block_my_external_backup_restore_courses'),
            block_my_external_backup_restore_courses_tools::STATUS_PERFORMED =>
                get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_PERFORMED,
                    'block_my_external_backup_restore_courses'),
            block_my_external_backup_restore_courses_tools::STATUS_ERROR =>
                get_string('status_'.block_my_external_backup_restore_courses_tools::STATUS_ERROR,
                    'block_my_external_backup_restore_courses')
        ),
            'status_'.$row->id, $row->status);
    }
    public function col_action(stdClass $row) {
        $out = html_writer::empty_tag('input',
            array('type' => 'hidden', 'value' => $this->currpage, 'name' => 'page'));
        $out .= html_writer::empty_tag('input',
            array('type' => 'submit', 'value' => get_string('edit'),
                'name' => 'submit', 'onclick' => '$(\'#trigger\').val('.$row->id.')'));
        return $out;
    }
    public function col_timecreated(stdClass $row) {
        return $row->timecreated == 0 ? get_string('never') : userdate($row->timecreated, '%D %X');
    }
    public function col_timemodified(stdClass $row) {
        return $row->timemodified == 0 ? get_string('never') : userdate($row->timemodified, '%D %X');
    }
    public function col_timescheduleprocessed(stdClass $row) {
        return $row->timescheduleprocessed == 0 ?
            get_string('never') : userdate($row->timescheduleprocessed, '%D %X');
    }

    public function start_html() {
        parent::start_html();
        echo html_writer::start_tag('form', array('action' => $this->baseurl->out()));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'trigger', 'id' => 'trigger'));

    }

    public function finish_html() {
        echo html_writer::end_tag('form');
        parent::finish_html();
    }
}
