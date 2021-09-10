<?php

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

$table = new rocketchat_admin_table($perpage,$page);
$table->is_persistent(true);
echo $table->out($table->get_rows_per_page(), true);

$table_recycle = new rocketchat_admin_table_recycle($perpage,$page);
$table_recycle->is_persistent(true);
echo $table_recycle->out($table->get_rows_per_page(), true);

echo $OUTPUT->footer();