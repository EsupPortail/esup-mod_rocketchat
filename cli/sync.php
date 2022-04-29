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
 * synchronisation CLI script for mod_rocketchat.
 * Enable to synchronize course enrolments with user and moderator enrolments in remote rocket.chat instance
 *
 * @package     mod_rocketchat
 * @subpackage  cli
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Celine Perves cperves@unistra.fr
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/mod/rocketchat/locallib.php');

// Get the cli options.
list($options, $unrecognized) = cli_get_params(array(
    'help' => false,
    'courseid' => null,
    'cmid' => null,
),
    array(
        'h' => 'help',
        'c' => 'courseid',
        'm' => 'cmid',
    ));

$help =
"
synchronisation CLI script for rocketchat module.
Enable to synchronize course enrolments with user and moderator enrolments in remote rocket.chat instance

Options:
-h, --help          Print out this help
-c, --courseid      "
."Concerned courseid where moodle Rocket.Chat module instance will be synchronised with remote Rocket.Chat associated groups
-m, --cmid          Concerned Rocket.Chat course module id  that  will be synchronised with its remote Rocket.Chat associated group
One of courseid or cmid is required
Example:
\$ sudo -u www-data /usr/bin/php /var/www/moodle/mod/rocketchat/cli/sync.php --courseid=xxx
$ sudo -u www-data /usr/bin/php /var/www/moodle/mod/rocketchat/cli/sync.php --cmid=xxx
";

if ($unrecognized) {
    $unrecognized = implode("\n\t", $unrecognized);
    cli_error("unknowned option  $unrecognized");
}

if ($options['help']) {
    cli_writeln($help);
    die();
}

if (empty($options['courseid']) && empty($options['cmid'])) {
    cli_error('courseid or cmid must be filled.');
}

if (!empty($options['courseid']) && !empty($options['cmid'])) {
    cli_error('Only one the 2 fields courseid or cmid must be filled.');
}
if (!empty($options['courseid'])) {
    $courseid = intval($options['courseid']);
    mod_rocketchat_tools::synchronize_group_members_for_course($courseid);
} else {
    $cmid = intval($options['cmid']);
    mod_rocketchat_tools::synchronize_group_members_for_module($cmid);
}
cli_writeln('sucessfully synchronized');
