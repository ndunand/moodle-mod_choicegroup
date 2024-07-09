<?php
// This file is part of the Choice group module for Moodle - http://moodle.org/
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
 * Choicegroup upgrade script.
 *
 * @package    mod_choicegroup
 * @copyright  2013 Université de Lausanne
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_choicegroup_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2013070900) {

        if ($oldversion < 2012042500) {

            // Remove the no longer needed choicegroup_answers DB table.
            $choicegroup_answers = new xmldb_table('choicegroup_answers');
            $dbman->drop_table($choicegroup_answers);

            // Change the choicegroup_options.text (text) field as choicegroup_options.groupid (int).
            $choicegroup_options = new xmldb_table('choicegroup_options');
            $field_text = new xmldb_field('text', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'choicegroupid');
            $field_groupid = new xmldb_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'choicegroupid');

            $dbman->rename_field($choicegroup_options, $field_text, 'groupid');
            $dbman->change_field_type($choicegroup_options, $field_groupid);

        }
        // Define table choicegroup to be created.
        $table = new xmldb_table('choicegroup');

        // Adding fields to table choicegroup.
        $newField = $table->add_field('multipleenrollmentspossible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $dbman->add_field($table, $newField);


        upgrade_mod_savepoint(true, 2013070900, 'choicegroup');
    }

    if ($oldversion < 2015022301) {
        $table = new xmldb_table('choicegroup');

        // Adding field to table choicegroup.
        $newField = $table->add_field('sortgroupsby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        if (!$dbman->field_exists($table, $newField)) {
            $dbman->add_field($table, $newField);
        }

        upgrade_mod_savepoint(true, 2015022301, 'choicegroup');
    }

    if ($oldversion < 2021071400) {

        // Define field maxenrollments to be added to choicegroup.
        $table = new xmldb_table('choicegroup');
        $field = new xmldb_field('maxenrollments', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'sortgroupsby');

        // Conditionally launch add field maxenrollments.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Choicegroup savepoint reached.
        upgrade_mod_savepoint(true, 2021071400, 'choicegroup');
    }

    if ($oldversion < 2021080500) {

        // Define field onlyactive to be added to choicegroup.
        $table = new xmldb_table('choicegroup');
        $field = new xmldb_field('onlyactive', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sortgroupsby');

        // Conditionally launch add field onlyactive.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Group choice savepoint reached.
        upgrade_mod_savepoint(true, 2021080500, 'choicegroup');
    }

    return true;
}
