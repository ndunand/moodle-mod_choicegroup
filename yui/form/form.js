/**
 * This is JavaScript code that handles drawing on mouse events and painting pre-existing drawings.
 * @package    qtype
 * @subpackage freehanddrawing
 * @copyright  ETHZ LET <jacob.shapiro@let.ethz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

YUI.add('moodle-mod_choicegroup-form', function(Y) {
	var CSS = {
	},
	SELECTORS = {

	};
	Y.namespace('Moodle.mod_choicegroup.form');
	Y.Moodle.mod_choicegroup.form = {
			init: function() {
				
				var availableGroupsNode = Y.one('#availablegroups');
				var addGroupButtonNode = Y.one('#addGroupButton');
				var selectedGroupsNode = Y.one('#id_selectedGroups');
				var removeGroupButtonNode = Y.one('#removeGroupButton');
				var formNode = Y.one('#mform1');
				var uiInputLimitNode = Y.one('#ui_limit_input');
				var applyLimitToAllGroupsButtonNode = Y.one("#id_setlimit");
				var limitAnswersSelectNode = Y.one('#id_limitanswers');
				var limitInputUIDIVNode = Y.one('#fitem_id_limit_0');
				
				
				function getInputLimitNodeOfSelectedGroupNode(n) {
					return Y.one('#group_' + n.get('value') + '_limit');
				}
				
				function cleanSelectedGroupsList() {
					var optionsNodes = Y.all("#id_selectedGroups option");
					optionsNodes.each(function(optNode) {
						if (optNode.get('parentNode') != null) {
						optNode.setContent(optNode.getContent().replace(/&nbsp;/gi,''));
						optionsNodes.each(function(opt2Node){
							if ((opt2Node != optNode) && (opt2Node.get('value') == optNode.get('value'))) {
								opt2Node.remove();
							}
						});
					}
					});
				}
				
				function addOtionNodeToSelectedGroupsList(optNode) {
					if (optNode.hasClass('grouping') == true) {
						// This is a grouping, so instead of adding this item we actually need to add everything underneath it
						var sib = optNode.next(); // sib means sibling, as in, the next element in the DOM tree
						while (sib.hasClass('nested') && sib.hasClass('group')) {
							// add sib
							selectedGroupsNode.append(sib.cloneNode(true));
							// go to next node
							sib = sib.next();
						}
					} else {
						selectedGroupsNode.append(optNode.cloneNode(true));
					}
				}
				
				function updateGroupLimit(e) {
					var selectedOptionsNodes = Y.all("#id_selectedGroups option:checked");
					// get value of input box
					var limit = uiInputLimitNode.get('value');
					selectedOptionsNodes.each(function(optNode) {
						getInputLimitNodeOfSelectedGroupNode(optNode).set('value', limit);
					});
				}
				
				
				

				// On click fill in the limit in every field
				applyLimitToAllGroupsButtonNode.on('click', function (e) {
					// Get the value string
					var generalLimitValue = Y.one("#id_generallimitation").get('value');
					// Make sure we've got an integer value
					generalLimitValue = parseInt(generalLimitValue);
					if (!isNaN(generalLimitValue)) {
						var limitInputNodes = Y.all("input.limit_input_node");
						limitInputNodes.each(function(n) { n.set('value', generalLimitValue); });
					} else {
						alert('The value you entered, ' + generalLimitValue + ', is not a number.')
					}
				});
				
				

				
				formNode.on('submit', function(e) {
					var selectedOptionsNodes = Y.all("#id_selectedGroups option");
					var serializedSelection = '';
					selectedOptionsNodes.each(function(optNode) { serializedSelection += ';' + optNode.get('value'); });
					Y.one('#serializedselectedgroups').set('value', serializedSelection);
				});
				
				
				availableGroupsNode.on('click', function(e) {
					var selectedOptionsNodes = Y.all("#availablegroups option:checked");
					if (selectedOptionsNodes.size() >= 2) {
						var allGroupings = true;
						selectedOptionsNodes.each(function(optNode){
							if (optNode.hasClass('grouping') == false) {
								allGroupings = false;
							}
						});
						if (allGroupings) {
							addGroupButtonNode.setContent('Add Groupings');
						} else {
							addGroupButtonNode.setContent('Add Groups');
						}
						addGroupButtonNode.set('disabled', false);
						
					} else if (selectedOptionsNodes.size() >= 1) {
						var firstNode = selectedOptionsNodes.item(0);
						if (firstNode.hasClass('grouping')) {
							addGroupButtonNode.setContent('Add Grouping');
						} else {
							addGroupButtonNode.setContent('Add Group');
						}
						addGroupButtonNode.set('disabled', false);
						
					} else {
						addGroupButtonNode.set('disabled', true);
						addGroupButtonNode.setContent('Add');	
					}

				});
				Y.delegate('dblclick', function(e) { 
					addOtionNodeToSelectedGroupsList(e.currentTarget);
					cleanSelectedGroupsList();
					
				},  Y.config.doc, "select[id='availablegroups'] option", this);
				
				selectedGroupsNode.on('click', function(e) {
					var selectedOptionsNodes = Y.all("#id_selectedGroups option:checked");
					if (selectedOptionsNodes.size() >= 2) {
						removeGroupButtonNode.setContent('Remove Groups');
						removeGroupButtonNode.set('disabled', false);
						uiInputLimitNode.set('disabled', true);
						uiInputLimitNode.set('value', 'multiple values');
						
					} else if (selectedOptionsNodes.size() >= 1) {
						removeGroupButtonNode.setContent('Remove Group');
						removeGroupButtonNode.set('disabled', false);
						uiInputLimitNode.set('disabled', false);
						uiInputLimitNode.set('value', getInputLimitNodeOfSelectedGroupNode(selectedOptionsNodes.item(0)).get('value'));
						
						
					} else {
						removeGroupButtonNode.set('disabled', true);
						removeGroupButtonNode.setContent('Add');
						uiInputLimitNode.set('disabled', true);
					}

				});
				
				uiInputLimitNode.on('change', function(e) { updateGroupLimit(e); });
				uiInputLimitNode.on('blur', function(e) { updateGroupLimit(e); });
				
				
				addGroupButtonNode.on('click', function(e) {
					var selectedOptionsNodes = Y.all("#availablegroups option:checked");
					selectedOptionsNodes.each(function(optNode) { addOtionNodeToSelectedGroupsList(optNode); });
					cleanSelectedGroupsList();
				});
				removeGroupButtonNode.on('click', function(e) {
					var selectedOptionsNodes = Y.all("#id_selectedGroups option:checked");
					selectedOptionsNodes.each(function(optNode) {
							optNode.remove();
						
					});
				});
				
				limitAnswersSelectNode.on('change', function(e) {
					if (limitAnswersSelectNode.get('value') == '1') { // limiting is enabled, show limit box
						limitInputUIDIVNode.show();
						
					} else { // limiting is disabled
						limitInputUIDIVNode.hide();
						
					}
					
				});

				
			},


	};
}, '@VERSION@', {requires: ['node', 'event'] });
