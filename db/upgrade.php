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
 * upgrade file
 *
 * @package     mod_rocketchat
 * @category    upgrade
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_rocketchat_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2020092903) {
        // Define table rocketchatxrecyclebin to be created.
        $table = new xmldb_table('rocketchatxrecyclebin');

        // Adding fields to table rocketchatxrecyclebin.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('binid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rocketchatid', XMLDB_TYPE_CHAR, '24', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table rocketchatxrecyclebin.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table rocketchatxrecyclebin.
        $table->add_index('uniquerocketchatid', XMLDB_INDEX_UNIQUE, array('rocketchatid'));

        // Conditionally launch create table for rocketchatxrecyclebin.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Rocketchat savepoint reached.
        upgrade_mod_savepoint(true, '2020092903', 'rocketchat');

    }
    if ($oldversion < 2020092904) {
        // Define index uniquerocketchatid (unique) to be dropped form rocketchat.
        $table = new xmldb_table('rocketchat');
        $index = new xmldb_index('uniquerocketchatid', XMLDB_INDEX_UNIQUE, array('rocketchatid'));

        // Conditionally launch drop index uniquerocketchatid.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rocketchat savepoint reached.
        upgrade_mod_savepoint(true, '2020092904', 'rocketchat');
    }
    if ($oldversion < 2020100901) {
        // Define field rocketchatname to be dropped from rocketchat.
        $table = new xmldb_table('rocketchat');
        $field = new xmldb_field('rocketchatname');

        // Conditionally launch drop field rocketchatname.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Rocketchat savepoint reached.
        upgrade_mod_savepoint(true, 2020100901, 'rocketchat');
    }
    if ($oldversion < 2020101203) {
        $table = new xmldb_table('rocketchat');
        $field = new xmldb_field('embbeded', XMLDB_TYPE_INTEGER, '1',
            null, null, null, '0', 'popupwidth');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->set_field('rocketchat', 'embbeded', 0);
            $dbman->change_field_notnull($table, $field);
        }
        upgrade_mod_savepoint(true, 2020101203, 'rocketchat');

    }

    if ($oldversion < 2021011500) {
        // Define field retention to be added to rocketchat.
        $table = new xmldb_table('rocketchat');
        $field = new xmldb_field('retention', XMLDB_TYPE_INTEGER, '10',
            null, null, null, null, 'userroles');

        // Conditionally launch add field retention.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Rocketchat savepoint reached.
        upgrade_mod_savepoint(true, 2021011500, 'rocketchat');
    }
    if ($oldversion < 2021011504) {
        // Define field retention to be added to rocketchat.
        $table = new xmldb_table('rocketchat');
        $field = new xmldb_field('retentionenabled', XMLDB_TYPE_INTEGER, '1',
            null, true, null, 0, 'userroles');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('overrideglobal', XMLDB_TYPE_INTEGER, '1',
            null, true, null, 0, 'retentionenabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('maxage', XMLDB_TYPE_INTEGER, '10',
            null, true, null, 90, 'overrideglobal');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('filesonly', XMLDB_TYPE_INTEGER, '1',
            null, true, null, 0, 'maxage');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('excludepinned', XMLDB_TYPE_INTEGER, '1',
            null, true, null, 0, 'filesonly');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Rocketchat savepoint reached.
        upgrade_mod_savepoint(true, 2021011504, 'rocketchat');
    }
    if ($oldversion < 2021020800) {
        $tokenmode = get_config('mod_rocketchat', 'tokenmode');
        if ($tokenmode) {
            $apipassword = get_config('mod_rocketchat', 'apipassword');
            set_config('apitoken', $apipassword, 'mod_rocketchat');
        } else {
            echo get_string('warningapiauthchanges', 'mod_rocketchat');
        }
        upgrade_mod_savepoint(true, 2021020800, 'rocketchat');
    }

    if ($oldversion < 2021072600) {
        $table = new xmldb_table('rocketchat');
        $field = new xmldb_field('overrideglobal');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2021072600, 'rocketchat');
    }
    return true;
}
