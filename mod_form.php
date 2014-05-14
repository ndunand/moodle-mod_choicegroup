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
 * Version information
 *
 * @package    mod
 * @subpackage choicegroup
 * @copyright  2013 Université de Lausanne
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_choicegroup_mod_form extends moodleform_mod {

	function definition() {
		global $CFG, $CHOICEGROUP_SHOWRESULTS, $CHOICEGROUP_PUBLISH, $CHOICEGROUP_DISPLAY, $DB, $COURSE, $PAGE;

		$mform    =& $this->_form;

		//-------------------------------------------------------------------------------
		$mform->addElement('header', 'general', get_string('general', 'form'));

		$mform->addElement('text', 'name', get_string('choicegroupname', 'choicegroup'), array('size'=>'64'));
		if (!empty($CFG->formatstringstriptags)) {
			$mform->setType('name', PARAM_TEXT);
		} else {
			$mform->setType('name', PARAM_CLEANHTML);
		}
		$mform->addRule('name', null, 'required', null, 'client');

		$this->add_intro_editor(true, get_string('chatintro', 'chat'));

		//-------------------------------------------------------------------------------


		// -------------------------
		// Fetch data from database
		// -------------------------
		$groups = array();
		$db_groups = $DB->get_records('groups', array('courseid' => $COURSE->id));
		foreach ($db_groups as $group) {
			$groups[$group->id] = new stdClass();
			$groups[$group->id]->name = $group->name;
			$groups[$group->id]->mentioned = false;
			$groups[$group->id]->id = $group->id;
		}

		if (count($db_groups) < 2) {
			print_error('pleasesetgroups', 'choicegroup', new moodle_url('/course/view.php?id='.$COURSE->id));
		}

		$db_groupings = $DB->get_records('groupings', array('courseid' => $COURSE->id));
		foreach ($db_groupings as $grouping) {
			$groupings[$grouping->id] = new stdClass();
			$groupings[$grouping->id]->name = $grouping->name;
		}

		$db_groupings_groups = $DB->get_records('groupings_groups');

		foreach ($db_groupings_groups as $grouping_group_link) {
			$groupings[$grouping_group_link->groupingid]->linkedGroupsIDs[] =  $grouping_group_link->groupid;
		}
		// -------------------------
		// -------------------------

		// -------------------------
		// Continue generating form
		// -------------------------
		$mform->addElement('header', 'miscellaneoussettingshdr', get_string('miscellaneoussettings', 'form'));
		$mform->setExpanded('miscellaneoussettingshdr');
		$mform->addElement('checkbox', 'multipleenrollmentspossible', get_string('multipleenrollmentspossible', 'choicegroup'));

		$mform->addElement('select', 'showresults', get_string("publish", "choicegroup"), $CHOICEGROUP_SHOWRESULTS);
		$mform->setDefault('showresults', CHOICEGROUP_SHOWRESULTS_DEFAULT);

		$mform->addElement('select', 'publish', get_string("privacy", "choicegroup"), $CHOICEGROUP_PUBLISH, CHOICEGROUP_PUBLISH_DEFAULT);
		$mform->setDefault('publish', CHOICEGROUP_PUBLISH_DEFAULT);
		$mform->disabledIf('publish', 'showresults', 'eq', 0);

		$mform->addElement('selectyesno', 'allowupdate', get_string("allowupdate", "choicegroup"));

		$mform->addElement('selectyesno', 'showunanswered', get_string("showunanswered", "choicegroup"));

		$menuoptions = array();
		$menuoptions[0] = get_string('disable');
		$menuoptions[1] = get_string('enable');
		$mform->addElement('select', 'limitanswers', get_string('limitanswers', 'choicegroup'), $menuoptions);
		$mform->addHelpButton('limitanswers', 'limitanswers', 'choicegroup');

		$mform->addElement('text', 'generallimitation', get_string('generallimitation', 'choicegroup'), array('size' => '6'));
		$mform->setType('generallimitation', PARAM_INT);
		$mform->disabledIf('generallimitation', 'limitanswers', 'neq', 1);
		$mform->addRule('generallimitation', get_string('error'), 'numeric', 'extraruledata', 'client', false, false);
		$mform->setDefault('generallimitation', 0);
		$mform->addElement('button', 'setlimit', get_string('applytoallgroups', 'choicegroup'));
		$mform->disabledIf('setlimit', 'limitanswers', 'neq', 1);


		// -------------------------
		// Generate the groups section of the form
		// -------------------------


		$mform->addElement('header', 'groups', 'groups');
		$mform->addElement('static', 'description', 'exercise1', 'exercise2');
		$mform->addElement('html', '<fieldset class="clearfix"><legend class="ftoggler">Groups</legend>
				<div class="fcontainer clearfix">
				<div id="fitem_id_option_0" class="fitem fitem_fselect ">
				<div class="fitemtitle"><label for="id_option_0">Group </label><span class="helptooltip"><a href="http://dmoodle2.ethz.ch/moodle/help.php?component=choicegroup&amp;identifier=choicegroupoptions&amp;lang=en" title="Help with Choice options" aria-haspopup="true" target="_blank"><img src="http://dmoodle2.ethz.ch/moodle/theme/image.php?theme=standard&amp;component=core&amp;image=help" alt="Help with Choice options" class="iconhelp"></a></span></div><div class="felement fselect">

				<table><tr><td>Available Groups</td><td>&nbsp;</td><td>Selected Groups</td><td>&nbsp;</td></tr><tr><td>');

		$mform->addElement('html','<select id="availablegroups" name="availableGroups" multiple size=10 style="width:200px">');
		foreach ($groupings as $groupingID => $grouping) {
			// find all linked groups to this grouping
			if (count($grouping->linkedGroupsIDs) > 1) { // grouping has more than 2 items, thus we should display it (otherwise it would be clearer to display only that single group alone)
				$mform->addElement('html', '<option value="'.$groupingID.'" style="font-weight: bold" class="grouping">['.$grouping->name.']</option>');
				foreach ($grouping->linkedGroupsIDs as $linkedGroupID) {
					$mform->addElement('html', '<option value="'.$linkedGroupID.'" class="group nested">&nbsp;&nbsp;&nbsp;&nbsp;'.$groups[$linkedGroupID]->name.'</option>');
					$groups[$linkedGroupID]->mentioned = true;
				}
			}
		}
		foreach ($groups as $group) {
			if ($group->mentioned === false) {
				$mform->addElement('html', '<option value="'.$group->id.'" class="group toplevel">'.$group->name.'</option>');
			}
		}
		$mform->addElement('html','</select>');






		$mform->addElement('html','
				</td><td><button id="addGroupButton" name="add" type="button" disabled>Add</button></td><td>');
		$mform->addElement('html','<select id="id_selectedGroups" name="selectedGroups" multiple size=10 style="width:200px"></select>');

		$mform->addElement('html','</td><td><div><button name="remove" type="button" disabled id="removeGroupButton">Remove</button></div><div><div id="fitem_id_limit_0" class="fitem fitem_ftext" style="display:none"><div class="fitemtitle"><label for="id_limit_0">Limit&nbsp;</label></div><div class="felement ftext">
				<input class="mod-choicegroup-limit-input" type="text" value="0" id="ui_limit_input" disabled="disabled"></div></div></div></td></tr></table>
				</div></div>
				 
				</div>
				</fieldset>');

		$mform->setExpanded('groups');

		foreach ($groups as $group) {
			$mform->addElement('hidden', 'group_' . $group->id . '_limit', '', array('id' => 'group_' . $group->id . '_limit', 'class' => 'limit_input_node'));
			$mform->setType('group_' . $group->id . '_limit', PARAM_RAW);
		}


		$serializedselectedgroupsValue = '';
		if (isset($this->_instance) && $this->_instance != '') {
			// this is presumably edit mode, try to fill in the data for javascript
			$cg = choicegroup_get_choicegroup($this->_instance);
			foreach ($cg->option as $optionID => $groupID) {
				$serializedselectedgroupsValue .= ';' . $groupID;
				$mform->setDefault('group_' . $groupID . '_limit', $cg->maxanswers[$optionID]);
			}
			 
		}


		$mform->addElement('hidden', 'serializedselectedgroups', $serializedselectedgroupsValue, array('id' => 'serializedselectedgroups'));
		$mform->setType('serializedselectedgroups', PARAM_RAW);
		
		// -------------------------
		// Go on the with the remainder of the form
		// -------------------------


		//-------------------------------------------------------------------------------
		$mform->addElement('header', 'timerestricthdr', get_string('timerestrict', 'choicegroup'));
		$mform->addElement('checkbox', 'timerestrict', get_string('timerestrict', 'choicegroup'));

		$mform->addElement('date_time_selector', 'timeopen', get_string("choicegroupopen", "choicegroup"));
		$mform->disabledIf('timeopen', 'timerestrict');

		$mform->addElement('date_time_selector', 'timeclose', get_string("choicegroupclose", "choicegroup"));
		$mform->disabledIf('timeclose', 'timerestrict');

		//-------------------------------------------------------------------------------
		$this->standard_coursemodule_elements();
		//-------------------------------------------------------------------------------
		$this->add_action_buttons();
}

function data_preprocessing(&$default_values){
	global $DB;
	$this->js_call();

	if (empty($default_values['timeopen'])) {
		$default_values['timerestrict'] = 0;
	} else {
		$default_values['timerestrict'] = 1;
	}

	}

	function validation($data, $files) {
		$errors = parent::validation($data, $files);

		$groupIDs = explode(';', $data['serializedselectedgroups']);
		$groupIDs = array_diff( $groupIDs, array( '' ) );

		if (array_key_exists('multipleenrollmentspossible', $data) && $data['multipleenrollmentspossible'] === '1') {
			if (count($groupIDs) < 1) {
				$errors['serializedselectedgroups'] = get_string('fillinatleastoneoption', 'choicegroup');
			}
		} else {
			if (count($groupIDs) < 2) {
				$errors['serializedselectedgroups'] = get_string('fillinatleasttwooptions', 'choicegroup');
			}
		}


		return $errors;
	}

	function get_data() {
		$data = parent::get_data();
		if (!$data) {
			return false;
		}
		// Set up completion section even if checkbox is not ticked
		if (empty($data->completionsection)) {
			$data->completionsection=0;
		}
		return $data;
	}

	function add_completion_rules() {
		$mform =& $this->_form;

		$mform->addElement('checkbox', 'completionsubmit', '', get_string('completionsubmit', 'choicegroup'));
		return array('completionsubmit');
	}

	function completion_rule_enabled($data) {
		return !empty($data['completionsubmit']);
	}

	public function js_call() {
		global $PAGE;
		$PAGE->requires->yui_module('moodle-mod_choicegroup-form', 'Y.Moodle.mod_choicegroup.form.init');
	}

}

