<?php

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // course

    $PAGE->set_url('/mod/choicegroup/index.php', array('id'=>$id));

    if (!$course = $DB->get_record('course', array('id'=>$id))) {
        print_error('invalidcourseid');
    }

    require_course_login($course);
    $PAGE->set_pagelayout('incourse');

    add_to_log($course->id, "choicegroup", "view all", "index.php?id=$course->id", "");

    $strchoicegroup = get_string("modulename", "choicegroup");
    $strchoicegroups = get_string("modulenameplural", "choicegroup");
    $strsectionname  = get_string('sectionname', 'format_'.$course->format);
    $PAGE->set_title($strchoicegroups);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strchoicegroups);
    echo $OUTPUT->header();

    if (! $choicegroups = get_all_instances_in_course("choicegroup", $course)) {
        notice(get_string('thereareno', 'moodle', $strchoicegroups), "../../course/view.php?id=$course->id");
    }

    $usesections = course_format_uses_sections($course->format);
    if ($usesections) {
        $sections = get_all_sections($course->id);
    }

    $sql = "SELECT cha.*
              FROM {choicegroup} ch, {choicegroup_answers} cha
             WHERE cha.choicegroupid = ch.id AND
                   ch.course = ? AND cha.userid = ?";

    $answers = array () ;
    if (isloggedin() and !isguestuser() and $allanswers = $DB->get_records_sql($sql, array($course->id, $USER->id))) {
        foreach ($allanswers as $aa) {
            $answers[$aa->choicegroupid] = $aa;
        }
        unset($allanswers);
    }


    $timenow = time();

    $table = new html_table();

    if ($usesections) {
        $table->head  = array ($strsectionname, get_string("question"), get_string("answer"));
        $table->align = array ("center", "left", "left");
    } else {
        $table->head  = array (get_string("question"), get_string("answer"));
        $table->align = array ("left", "left");
    }

    $currentsection = "";

    foreach ($choicegroups as $choicegroup) {
        if (!empty($answers[$choicegroup->id])) {
            $answer = $answers[$choicegroup->id];
        } else {
            $answer = "";
        }
        if (!empty($answer->optionid)) {
            $aa = format_string(choicegroup_get_option_text($choicegroup, $answer->optionid));
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

        //Calculate the href
        if (!$choicegroup->visible) {
            //Show dimmed if the mod is hidden
            $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$choicegroup->coursemodule\">".format_string($choicegroup->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $tt_href = "<a href=\"view.php?id=$choicegroup->coursemodule\">".format_string($choicegroup->name,true)."</a>";
        }
        if ($usesections) {
            $table->data[] = array ($printsection, $tt_href, $aa);
        } else {
            $table->data[] = array ($tt_href, $aa);
        }
    }
    echo "<br />";
    echo html_writer::table($table);

    echo $OUTPUT->footer();


