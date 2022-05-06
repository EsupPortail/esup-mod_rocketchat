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

use mod_rocketchat\api\manager\rocket_chat_api_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/rocketchat/locallib.php');

class rocketchat_admin_table extends table_sql implements renderable {
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
        parent::__construct('rocketchat');
        if (isset($perpage)) {
            $this->perpage = $perpage;
        }
        if (isset($page)) {
            $this->currpage = $page;
        }

        $this->define_baseurl(new moodle_url('/mod/rocketchat/management.php'));

        $this->anyentries = $DB->get_records('rocketchat');

        if ($rowoffset) {
            $this->rownum = $rowoffset - 1;
        }

        $params = array('component' => 'course',
            'filearea' => 'legacy',
            'coursecontext' => CONTEXT_COURSE);
        $fields = '*';
        $from = '{rocketchat}';
        $where = 'true';

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql('select count(*) from {rocketchat}');

        $columns = array();
        $headers = array();

        $columns[] = 'action';
        $headers[] = 'Manage';
        $columns[] = $headers[] = 'id';
        $columns[] = $headers[] = 'rocketchatid';
        $columns[] = $headers[] = 'course';
        $columns[] = $headers[] = 'name';
        $columns[] = $headers[] = 'timecreated';
        $columns[] = $headers[] = 'timemodified';
        $columns[] = $headers[] = 'retentionenabled';
        $columns[] = $headers[] = 'filesonly';
        $columns[] = $headers[] = 'maxage';

        // ...set the columns
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->sortable(true, 'course');
        $this->no_sorting('action');
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

    public function col_course(stdClass $row) {
        if ($row->course) {
            return html_writer::link(new moodle_url('/course/view.php',
                array('id' => $row->course)), $row->course);

        } else {
            return '';
        }
    }

    public function col_action(stdClass $row) {
        $config = get_config('mod_rocketchat');
        $rocketchatapimanager = new rocket_chat_api_manager();
        $result = null;
        try {
            $channel = $rocketchatapimanager->get_rocketchat_room_object($row->rocketchatid);
            $result = $channel->info();
        } catch (Exception $e) {
            $result = null;
        }
        if (!$result) { // Not Found!
            $results = 'error';
            $rocketid = 0;

        } else { // Found!
            $results = 'details';
            $rocketid = $row->rocketchatid;
        }
        $out = html_writer::div(html_writer::link(
            new moodle_url('/mod/rocketchat/rocket_room_details.php',
                ['rocketchat_id' => $rocketid,
                    'module_id' => $row->id,
                    'course_id' => $row->course,
                    'sesskey' => sesskey()]),
            get_string($results, "mod_rocketchat")), 'rocketchat_synchronise_task');
        return $out;
    }
    public function col_timecreated(stdClass $row) {
        return $row->timecreated == 0 ? get_string('never') : userdate($row->timecreated, '%D %X');
    }
    public function col_timemodified(stdClass $row) {
        return $row->timemodified == 0 ? get_string('never') : userdate($row->timemodified, '%D %X');
    }

    public function col_maxage(stdClass $row) {
        return $row->maxage . ' ' .get_string('days');
    }

    public function col_filesonly(stdClass $row) {
        return $row->filesonly == 0 ? get_string('no') : get_string('yes');
    }

    public function col_retentionenabled(stdClass $row) {
        return $row->retentionenabled == 0 ? get_string('no') : get_string('yes');
    }

    // ...override fonctions to include form
    public function start_html() {

        parent::start_html();
        echo html_writer::start_tag('form', array('action' => $this->baseurl->out()));
        echo html_writer::empty_tag('input',
            array('type' => 'hidden',
                'name' => 'trigger',
                'id' => 'trigger'));
    }

    public function finish_html() {
        echo html_writer::end_tag('form');
        parent::finish_html();
    }
}
