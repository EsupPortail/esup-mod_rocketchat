<?php

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
}