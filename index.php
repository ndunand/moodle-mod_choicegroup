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
 * This page lists all the instances of choicegroup in a particular course
 *
 * @package    mod_choicegroup
 * @copyright  2013 Université de Lausanne
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT); // Course.

$PAGE->set_url('/mod/choicegroup/index.php', ['id' => $id]);

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new moodle_exception('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

$params = [
    'context' => context_course::instance($course->id),
];
$event = \mod_choicegroup\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strchoicegroup = get_string("modulename", "choicegroup");
$strchoicegroups = get_string("modulenameplural", "choicegroup");
$strsectionname = get_string('sectionname', 'format_' . $course->format);
$PAGE->set_title($strchoicegroups);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strchoicegroups);
echo $OUTPUT->header();

if (!$choicegroups = get_all_instances_in_course("choicegroup", $course)) {
    notice(get_string('thereareno', 'moodle', $strchoicegroups), "../../course/view.php?id=$course->id");
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $modinfo = get_fast_modinfo($course->id);
    $sections = $modinfo->get_section_info_all();
}

$table = new html_table();

if ($usesections) {
    $table->head = [$strsectionname, get_string("question"), get_string("answer")];
    $table->align = ["center", "left", "left"];
} else {
    $table->head = [get_string("question"), get_string("answer")];
    $table->align = ["left", "left"];
}

$currentsection = "";

foreach ($choicegroups as $choicegroup) {
    $choicegroupgroups = choicegroup_get_groups($choicegroup);

    $answers = choicegroup_get_user_answer($choicegroup, $USER->id, true);
    if (!empty($answers)) {
        $aa = [];

        foreach ($answers as $answer) {
            $aa[] = $answer->name;
        }

        $aa = implode(', ', $aa);
    } else {
        $aa = "";
    }

    if ($usesections) {
        $printsection = "";
        if ($choicegroup->section !== $currentsection) {
            if ($choicegroup->section) {
                $printsection = get_section_name($course, $sections[$choicegroup->section]);
            }

            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }

            $currentsection = $choicegroup->section;
        }
    }

    // Calculate the href.
    if (!$choicegroup->visible) {
        // Show dimmed if the mod is hidden.
        $tthref = "<a class=\"dimmed\" href=\"view.php?id=$choicegroup->coursemodule\">" .
            format_string($choicegroup->name, true) . "</a>";
    } else {
        // Show normal if the mod is visible.
        $tthref = "<a href=\"view.php?id=$choicegroup->coursemodule\">" . format_string($choicegroup->name, true) . "</a>";
    }

    if ($usesections) {
        $table->data[] = [$printsection, $tthref, $aa];
    } else {
        $table->data[] = [$tthref, $aa];
    }
}

echo "<br />";
echo html_writer::table($table);

echo $OUTPUT->footer();
