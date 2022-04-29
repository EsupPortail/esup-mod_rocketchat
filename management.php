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
 * admin page for rocketchat channels management
 * @package mod_rocketchat
 * @author Matthieu Fuchs <matfuchs@unistra.fr>
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2021 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/rocketchat/rocketchatadmintable.php');
require_once($CFG->dirroot . '/mod/rocketchat/rocketchatadmintablerecyclebin.php');
require_login(null, false);

admin_externalpage_setup('mod_rocketchat_admin_interface', '', array(),
    new moodle_url('/mod/rocketchat/managment.php', array()));
$PAGE->navbar->add(get_string('pluginname_admin', 'mod_rocketchat'));
$PAGE->requires->jquery();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('welcome_string', 'mod_rocketchat'));

$perpage = optional_param('perpage', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

$table = new rocketchat_admin_table($perpage, $page);
$table->is_persistent(true);
echo $table->out($table->get_rows_per_page(), true);

$tablerecycle = new rocketchat_admin_table_recycle($perpage, $page);
$tablerecycle->is_persistent(true);
echo $tablerecycle->out($table->get_rows_per_page(), true);

echo $OUTPUT->footer();
