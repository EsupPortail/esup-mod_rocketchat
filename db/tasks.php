<?php
/**
 * Folder plugin version information
 *
 * @package
 * @subpackage
 * @copyright  2021 unistra  {@link http://unistra.fr}
 * @author Matthieu Fuchs <matfuchs@unistra.fr> Celine Perves <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'mod_rocketchat\task\rocketchat_synchronise_task',
        'blocking' => 0,
        'minute' => '00',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
        'disabled' => 1
    )
);