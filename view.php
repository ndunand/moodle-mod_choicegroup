<?php

    require_once("../../config.php");
    require_once("lib.php");
    require_once($CFG->dirroot.'/group/lib.php');
    require_once($CFG->libdir . '/completionlib.php');

    $id         = required_param('id', PARAM_INT);                 // Course Module ID
    $action     = optional_param('action', '', PARAM_ALPHA);
    $attemptids = optional_param_array('attemptid', array(), PARAM_INT); // array of attempt ids for delete action

    $url = new moodle_url('/mod/choicegroup/view.php', array('id'=>$id));
    if ($action !== '') {
        $url->param('action', $action);
    }
    $PAGE->set_url($url);

    if (! $cm = get_coursemodule_from_id('choicegroup', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    require_course_login($course, false, $cm);

    if (!$choicegroup = choicegroup_get_choicegroup($cm->instance)) {
        print_error('invalidcoursemodule');
    }

    $strchoicegroup = get_string('modulename', 'choicegroup');
    $strchoicegroups = get_string('modulenameplural', 'choicegroup');

    if (!$context = get_context_instance(CONTEXT_MODULE, $cm->id)) {
        print_error('badcontext');
    }

    if ($action == 'delchoicegroup' and confirm_sesskey() and is_enrolled($context, NULL, 'mod/choicegroup:choose') and $choicegroup->allowupdate) {
        // user wants to delete his own choice:
        if ($answer = $DB->get_record('choicegroup_answers', array('choicegroupid' => $choicegroup->id, 'userid' => $USER->id))) {
            $old_option = $DB->get_record('choicegroup_options', array('id' => $answer->optionid));
            groups_remove_member($old_option->text, $USER->id);
            $DB->delete_records('choicegroup_answers', array('id' => $answer->id));

            // Update completion state
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $choicegroup->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE);
            }
        }
    }

    $PAGE->set_title(format_string($choicegroup->name));
    $PAGE->set_heading($course->fullname);

/// Mark as viewed
    $completion=new completion_info($course);
    $completion->set_module_viewed($cm);

/// Submit any new data if there is any
    if (data_submitted() && is_enrolled($context, NULL, 'mod/choicegroup:choose') && confirm_sesskey()) {
        $timenow = time();
        if (has_capability('mod/choicegroup:deleteresponses', $context)) {
            if ($action == 'delete') { //some responses need to be deleted
                choicegroup_delete_responses($attemptids, $choicegroup, $cm, $course); //delete responses.
                redirect("view.php?id=$cm->id");
            }
        }
        $answer = optional_param('answer', '', PARAM_INT);

        if (empty($answer)) {
            redirect("view.php?id=$cm->id", get_string('mustchooseone', 'choicegroup'));
        } else {
            choicegroup_user_submit_response($answer, $choicegroup, $USER->id, $course, $cm);
        }
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('choicegroupsaved', 'choicegroup'),'notifysuccess');
    } else {
        echo $OUTPUT->header();
    }


/// Display the choicegroup and possibly results
    add_to_log($course->id, "choicegroup", "view", "view.php?id=$cm->id", $choicegroup->id, $cm->id);

    /// Check to see if groups are being used in this choicegroup
    $groupmode = groups_get_activity_groupmode($cm);

    if ($groupmode) {
        groups_get_activity_group($cm, true);
        groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/choicegroup/view.php?id='.$id);
    }
    $allresponses = choicegroup_get_response_data($choicegroup, $cm, $groupmode);   // Big function, approx 6 SQL calls per user


    if (has_capability('mod/choicegroup:readresponses', $context)) {
        choicegroup_show_reportlink($allresponses, $cm);
    }

    echo '<div class="clearer"></div>';

    if ($choicegroup->intro) {
        echo $OUTPUT->box(format_module_intro('choicegroup', $choicegroup, $cm->id), 'generalbox', 'intro');
    }

    $current = false;  // Initialise for later
    //if user has already made a selection, and they are not allowed to update it, show their selected answer.
    if (isloggedin() && ($current = $DB->get_record('choicegroup_answers', array('choicegroupid' => $choicegroup->id, 'userid' => $USER->id))) && empty($choicegroup->allowupdate) ) {
        echo $OUTPUT->box(get_string("yourselection", "choicegroup", userdate($choicegroup->timeopen)).": ".format_string(choicegroup_get_option_text($choicegroup, $current->optionid)), 'generalbox', 'yourselection');
    }

/// Print the form
    $choicegroupopen = true;
    $timenow = time();
    if ($choicegroup->timeclose !=0) {
        if ($choicegroup->timeopen > $timenow ) {
            echo $OUTPUT->box(get_string("notopenyet", "choicegroup", userdate($choicegroup->timeopen)), "generalbox notopenyet");
            echo $OUTPUT->footer();
            exit;
        } else if ($timenow > $choicegroup->timeclose) {
            echo $OUTPUT->box(get_string("expired", "choicegroup", userdate($choicegroup->timeclose)), "generalbox expired");
            $choicegroupopen = false;
        }
    }

    $options = choicegroup_prepare_options($choicegroup, $USER, $cm, $allresponses);
    $renderer = $PAGE->get_renderer('mod_choicegroup');
    if ( (!$current or $choicegroup->allowupdate) and $choicegroupopen and is_enrolled($context, NULL, 'mod/choicegroup:choose')) {
    // They haven't made their choicegroup yet or updates allowed and choicegroup is open

        echo $renderer->display_options($options, $cm->id, $choicegroup->display, $choicegroup->publish, $choicegroup->limitanswers, $choicegroup->showresults, $current, $choicegroupopen, false);
    } else {
        // form can not be updated
        echo $renderer->display_options($options, $cm->id, $choicegroup->display, $choicegroup->publish, $choicegroup->limitanswers, $choicegroup->showresults, $current, $choicegroupopen, true);
    }
    $choicegroupformshown = true;

    $sitecontext = get_context_instance(CONTEXT_SYSTEM);

    if (isguestuser()) {
        // Guest account
        echo $OUTPUT->confirm(get_string('noguestchoose', 'choicegroup').'<br /><br />'.get_string('liketologin'),
                        get_login_url(), new moodle_url('/course/view.php', array('id'=>$course->id)));
    } else if (!is_enrolled($context)) {
        // Only people enrolled can make a choicegroup
        $SESSION->wantsurl = $FULLME;
        $SESSION->enrolcancel = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';

        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));

        echo $OUTPUT->box_start('generalbox', 'notice');
        echo '<p align="center">'. get_string('notenrolledchoose', 'choicegroup') .'</p>';
        echo $OUTPUT->container_start('continuebutton');
        echo $OUTPUT->single_button(new moodle_url('/enrol/index.php?', array('id'=>$course->id)), get_string('enrolme', 'core_enrol', $courseshortname));
        echo $OUTPUT->container_end();
        echo $OUTPUT->box_end();

    }

    // print the results at the bottom of the screen
    if ( $choicegroup->showresults == CHOICEGROUP_SHOWRESULTS_ALWAYS or
        ($choicegroup->showresults == CHOICEGROUP_SHOWRESULTS_AFTER_ANSWER and $current) or
        ($choicegroup->showresults == CHOICEGROUP_SHOWRESULTS_AFTER_CLOSE and !$choicegroupopen)) {
/*
        if (!empty($choicegroup->showunanswered)) {
            $choicegroup->option[0] = get_string('notanswered', 'choicegroup');
            $choicegroup->maxanswers[0] = 0;
        }
        $results = prepare_choicegroup_show_results($choicegroup, $course, $cm, $allresponses);
        $renderer = $PAGE->get_renderer('mod_choicegroup');
        echo $renderer->display_result($results);
*/
    }
    else if ($choicegroup->showresults == CHOICEGROUP_SHOWRESULTS_NOT) {
        echo $OUTPUT->box(get_string('neverresultsviewable', 'choicegroup'));
    }
    else if ($choicegroup->showresults == CHOICEGROUP_SHOWRESULTS_AFTER_ANSWER && !$current) {
        echo $OUTPUT->box(get_string('afterresultsviewable', 'choicegroup'));
    }
    else if ($choicegroup->showresults == CHOICEGROUP_SHOWRESULTS_AFTER_CLOSE and $choicegroupopen) {
        echo $OUTPUT->box(get_string('notyetresultsviewable', 'choicegroup'));
    }
    else if (!$choicegroupformshown) {
        echo $OUTPUT->box(get_string('noresultsviewable', 'choicegroup'));
    }

    echo $OUTPUT->footer();


