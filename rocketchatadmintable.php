<?php

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

class rocketchat_admin_table extends table_sql implements renderable {
    /** @var int $perpage */
    private $perpage = 10;
    /** @var int $rownum (global index of current row in table) */
    private $rownum = -1;
    /** @var renderer_base for getting output */
    private $output = null;
    /** @var boolean $any - True if there is one or more entries*/
    public $anyentry = false;
