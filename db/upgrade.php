<?php

// This file keeps track of upgrades to
// the choicegroup module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

function xmldb_choicegroup_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();


/*
//===== 1.9.0 upgrade line ======//

    if ($oldversion < 2009042000) {

    /// Rename field text on table choicegroup to text
        $table = new xmldb_table('choicegroup');
        $field = new xmldb_field('text', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'name');

    /// Launch rename field text
        $dbman->rename_field($table, $field, 'intro');

    /// choicegroup savepoint reached
        upgrade_mod_savepoint(true, 2009042000, 'choicegroup');
    }

    if ($oldversion < 2009042001) {

    /// Rename field format on table choicegroup to format
        $table = new xmldb_table('choicegroup');
        $field = new xmldb_field('format', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

    /// Launch rename field format
        $dbman->rename_field($table, $field, 'introformat');

    /// choicegroup savepoint reached
        upgrade_mod_savepoint(true, 2009042001, 'choicegroup');
    }

    if ($oldversion < 2010101300) {

        // Define field completionsubmit to be added to choicegroup
        $table = new xmldb_table('choicegroup');
        $field = new xmldb_field('completionsubmit', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field completionsubmit
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // choicegroup savepoint reached
        upgrade_mod_savepoint(true, 2010101300, 'choicegroup');
    }

*/

    return true;
}


