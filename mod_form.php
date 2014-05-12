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
        //$groups = array('' => get_string('choosegroup', 'choicegroup'));
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

        

       /* $repeatarray = array();
        $repeatarray[] = $mform->createElement('header', '', get_string('option','choicegroup').' {no}');
        $repeatarray[] = $mform->createElement('select', 'option', get_string('option','choicegroup'), $groups);
        $repeatarray[] = $mform->createElement('text', 'limit', get_string('limit','choicegroup'), array('class' => 'mod-choicegroup-limit-input'));
        $repeatarray[] = $mform->createElement('hidden', 'optionid', 0);
        */

//-------------------------------------------------------------------------------
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

        $db_groupings = $DB->get_records('groupings', array('courseid' => $COURSE->id));  
        foreach ($db_groupings as $grouping) { 
        	$groupings[$grouping->id] = new stdClass();
            $groupings[$grouping->id]->name = $grouping->name;  
        }  

        $db_groupings_groups = $DB->get_records('groupings_groups');

        foreach ($db_groupings_groups as $grouping_group_link) {
        	$groupings[$grouping_group_link->groupingid]->linkedGroupsIDs[] =  $grouping_group_link->groupid; 
        }

        
        
        $mform->addElement('header', 'groups', 'groups');
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
        
       	
        
        
//         $mform->addElement('html','<div id="availableGroupsTree"><ul>');
//         foreach ($groupings as $grouping) {
//         	// find all linked groups to this grouping
//         	if (count($grouping->linkedGroupsIDs) > 1) { // grouping has more than 2 items, thus we should display it (otherwise it would be clearer to display only that single group alone)
// 				$mform->addElement('html', '<li class="grouping">'.$grouping->name.'<ul>');
//         		foreach ($grouping->linkedGroupsIDs as $linkedGroupID) {
//         			$mform->addElement('html', '<li class="group">'.$groups[$linkedGroupID]->name.'</li>');
//         			$groups[$linkedGroupID]->mentioned = true;
//         		}
//         		$mform->addElement('html','</ul>');
//         	}
//         }
//         foreach ($groups as $group) {
//         	if ($group->mentioned === false) {
//         		$mform->addElement('html', '<li class="group">'.$group->name.'</li>');
//         	}
//         }
//         $mform->addElement('html','</ul></div>');
        
        
        
        
        
        $mform->addElement('html','
        		</td><td><button id="addGroupButton" name="add" type="button" disabled>Add</button></td><td>');
        $mform->addElement('html','<select id="id_selectedGroups" name="selectedGroups" multiple size=10 style="width:200px"></select>');
        //$selectedGroups = $mform->addElement('select', 'selectedGroups', '', array(),array('style'=>'width:70px'));
        //$selectedGroups->setMultiple(true);

        $mform->addElement('html','</td><td><div><button name="remove" type="button" disabled id="removeGroupButton">Remove</button></div><div><div id="fitem_id_limit_0" class="fitem fitem_ftext" style="display:none"><div class="fitemtitle"><label for="id_limit_0">Limit&nbsp;</label></div><div class="felement ftext">
        		<input class="mod-choicegroup-limit-input" type="text" value="0" id="ui_limit_input" disabled="disabled"></div></div></div></td></tr></table>
        		</div></div>
        		 
        		</div>
        		</fieldset>');
        
        $mform->setExpanded('groups');
        
		$mform->addElement('hidden', 'serializedselectedgroups', '', array('id' => 'serializedselectedgroups'));
        $mform->setType('serializedselectedgroups', PARAM_RAW);

		foreach ($groups as $group) {
			$mform->addElement('hidden', 'group_' . $group->id . '_limit', '', array('id' => 'group_' . $group->id . '_limit', 'class' => 'limit_input_node'));
        	$mform->setType('group_' . $group->id . '_limit', PARAM_RAW);
		}




/*        $repeatno = count($db_groups);
        $repeateloptions = array();
        $repeateloptions['limit']['default'] = 0;
        $repeateloptions['limit']['disabledif'] = array('limitanswers', 'eq', 0);
        $repeateloptions['limit']['rule'] = 'numeric';
        $repeateloptions['limit']['type'] = PARAM_INT;

        $repeateloptions['option']['helpbutton'] = array('choicegroupoptions', 'choicegroup');*/
//        $mform->setType('option', PARAM_CLEANHTML);

        $mform->setType('optionid', PARAM_INT);

   //     $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'option_repeats', 'option_add_fields', 3);

        // Remove "Add Fields" button as there are always enough fields
        //$mform->removeElement('option_add_fields');

        // If this groupchoice activity is newly created, fill the groupchoice fields
        // with all available groups in this course
        if(!$this->_instance) {
            $counter = 0;
            foreach($db_groups as &$i) {
                //$mform->getElement('option['.$counter.']')->setSelected($i->id);
                $counter++;
            }
        }


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
        if (!empty($this->_instance) && ($options = $DB->get_records_menu('choicegroup_options',array('choicegroupid'=>$this->_instance), 'id', 'id,groupid'))
               && ($options2 = $DB->get_records_menu('choicegroup_options', array('choicegroupid'=>$this->_instance), 'id', 'id,maxanswers')) ) {
            $choicegroupids=array_keys($options);
            $options=array_values($options);
            $options2=array_values($options2);

            foreach (array_keys($options) as $key){
                $default_values['option['.$key.']'] = $options[$key];
                $default_values['limit['.$key.']'] = $options2[$key];
                $default_values['optionid['.$key.']'] = $choicegroupids[$key];
            }

        }
        if (empty($default_values['timeopen'])) {
            $default_values['timerestrict'] = 0;
        } else {
            $default_values['timerestrict'] = 1;
        }

    }

    function validation($data, $files) {
    	return null;
        $errors = parent::validation($data, $files);

        $choicegroups = 0;
//         foreach ($data['option'] as $option){
//             if (trim($option) != ''){
//                 $choicegroups++;
//             }
//         }
        
        if (array_key_exists('multipleenrollmentspossible', $data) && $data['multipleenrollmentspossible'] === '1') {
            if ($choicegroups < 1) {
                $errors['option[0]'] = get_string('fillinatleastoneoption', 'choicegroup');
            }
        } else {
            if ($choicegroups < 2) {
                $errors['option[0]'] = get_string('fillinatleasttwooptions', 'choicegroup');
                $errors['option[1]'] = get_string('fillinatleasttwooptions', 'choicegroup');
            }
        }

//         $groups_selected = array();
//         $opt_id = 0;
//         foreach ($data['option'] as $option){
//             if (in_array($option, $groups_selected)) {
//                 $errors['option['.$opt_id.']'] = get_string('samegroupused', 'choicegroup');
//             }
//             elseif ($option) {
//                 $groups_selected[] = $option;
//             }
//             $opt_id++;
//         }

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

