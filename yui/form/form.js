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
				
				// -------------------------------
				// Global Variables 
				// -------------------------------
				
				var availableGroupsNode = Y.one('#availablegroups');
				var addGroupButtonNode = Y.one('#addGroupButton');
				var selectedGroupsNode = Y.one('#id_selectedGroups');
				var removeGroupButtonNode = Y.one('#removeGroupButton');
				var formNode = Y.one('#mform1');
				var uiInputLimitNode = Y.one('#ui_limit_input');
				var applyLimitToAllGroupsButtonNode = Y.one("#id_setlimit");
				var limitAnswersSelectNode = Y.one('#id_limitanswers');
				var limitInputUIDIVNode = Y.one('#fitem_id_limit_0');
				var expandButtonNode = Y.one('#expandButton');
				var collapseButtonNode = Y.one('#collapseButton');
				
				var groupingsNodesContainer = new Array();
				
				// --------------------------------
				// Global Functions
				// --------------------------------
				
				
				function removeElementFromArray(ar, from, to) {
					  var rest = ar.slice((to || from) + 1 || ar.length);
					  ar.length = from < 0 ? ar.length + from : from;
					  return ar.push.apply(ar, rest);
				}
				
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
				
				function addOptionNodeToSelectedGroupsList(optNode) {
					if (optNode.hasClass('grouping') == true) {
						// check if option is collapsed
						if (((typeof groupingsNodesContainer[optNode.get('value')]) == 'undefined') || ( groupingsNodesContainer[optNode.get('value')].length == 0)) {
							// it is expanded, take nodes from UI
							// This is a grouping, so instead of adding this item we actually need to add everything underneath it
							var sib = optNode.next(); // sib means sibling, as in, the next element in the DOM tree
							while (sib.hasClass('nested') && sib.hasClass('group')) {
								// add sib
								selectedGroupsNode.append(sib.cloneNode(true));
								// go to next node
								sib = sib.next();
							}
						} else {
							// yes it IS collapsed, need to take the nodes from the container rather than from the UI
							groupingsNodesContainer[optNode.get('value')].forEach(function (underlyingGroupNode) {
								selectedGroupsNode.append(underlyingGroupNode.cloneNode(true));
							});
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
				
				function collapseGrouping(groupingNode) {
					var sib = groupingNode.next(); // sib means sibling, as in, the next element in the DOM tree
					while (sib.hasClass('nested') && sib.hasClass('group')) {
						// save this node somewhere first
						if (typeof groupingsNodesContainer[groupingNode.get('value')] == 'undefined') {
							groupingsNodesContainer[groupingNode.get('value')] = new Array();
						}
						groupingsNodesContainer[groupingNode.get('value')].push(sib.cloneNode(true));
						// save the next node before removing the current one
						var nextSibling = sib.next();
						sib.remove();
						// go to next node
						sib = nextSibling;
					}
				}
				
				function expandGrouping(groupingNode) {
					var nextOpt = groupingNode.next();
					if (typeof groupingsNodesContainer[groupingNode.get('value')] != 'undefined') {
						groupingsNodesContainer[groupingNode.get('value')].forEach(function(underlyingGroupNode) {
							if (typeof nextOpt != 'undefined') {
								Y.all("#availablegroups").insertBefore(underlyingGroupNode, nextOpt);
							} else {
								Y.all("#availablegroups").appendChild(underlyingGroupNode);
							}
						});
						groupingsNodesContainer[groupingNode.get('value')] = new Array();
					}
						
					
				}
				
				function collapseAllGroupings() {
					var availableOptionsNodes = Y.all("#availablegroups option");
					availableOptionsNodes.each(function(optNode) {
						if (optNode.hasClass('grouping') == true) {
							collapseGrouping(optNode);
						}
					});
				}
				
				function expandAllGroupings() {
					var availableOptionsNodes = Y.all("#availablegroups option");
					availableOptionsNodes.each(function(optNode) {
						if (optNode.hasClass('grouping') == true) {
							expandGrouping(optNode);
						}
					});
				}
				
				
				
				// --------------------------------
				// this code happens on form load
				// --------------------------------
				if (Y.one('#serializedselectedgroups').get('value') != '') {
					var selectedGroups = Y.one('#serializedselectedgroups').get('value').split(';');
					selectedGroups = selectedGroups.filter(function(n) {return n != '';});
					var availableOptionsNodes = Y.all("#availablegroups option");
					availableOptionsNodes.each(function(optNode) {
						selectedGroups.forEach(function (selectedGroup) {
							if (selectedGroup == optNode.get('value')) {
								addOptionNodeToSelectedGroupsList(optNode);
							}
						});
					});
					cleanSelectedGroupsList();
				}
				
				if (limitAnswersSelectNode.get('value') == '1') { // limiting is enabled, show limit box
					limitInputUIDIVNode.show();
					
				} else { // limiting is disabled
					limitInputUIDIVNode.hide();
					
				}
				
				// Collapse all groupings on load
				
				
				
				collapseAllGroupings();
				Y.one('#expandButton').set('disabled', false);

				
				
				// -------------------------------
				// -------------------------------
				
				
				
				

				
				// ---------------------------------
				// Setup UI Bindings (on load)
				// ---------------------------------
				
				
				Y.one('#expandButton').on('click', function(e) {
					expandAllGroupings();
					Y.one('#expandButton').set('disabled', true);
					Y.one('#collapseButton').set('disabled', false);
					
				});
				Y.one('#collapseButton').on('click', function(e) {
					collapseAllGroupings();
					Y.one('#collapseButton').set('disabled', true);
					Y.one('#expandButton').set('disabled', false);
					
				});
				

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
					if (selectedOptionsNodes.size() < 2) {
						alert('You must select at least two group choices.');
				        e.preventDefault();
				        e.stopPropagation();
					}
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
					if (e.currentTarget.hasClass('grouping') == true) {
						if (((typeof groupingsNodesContainer[e.currentTarget.get('value')]) == 'undefined') || ( groupingsNodesContainer[e.currentTarget.get('value')].length == 0)) {
							collapseGrouping(e.currentTarget);
							Y.one('#expandButton').set('disabled', false);
						} else {
							expandGrouping(e.currentTarget);
							Y.one('#collapseButton').set('disabled', false);
						}

					} else {
						addOptionNodeToSelectedGroupsList(e.currentTarget);
						cleanSelectedGroupsList();
					}

					
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
						removeGroupButtonNode.setContent('Remove');
						uiInputLimitNode.set('disabled', true);
					}

				});
				
				uiInputLimitNode.on('change', function(e) { updateGroupLimit(e); });
				uiInputLimitNode.on('blur', function(e) { updateGroupLimit(e); });
				
				
				addGroupButtonNode.on('click', function(e) {
					var selectedOptionsNodes = Y.all("#availablegroups option:checked");
					selectedOptionsNodes.each(function(optNode) { addOptionNodeToSelectedGroupsList(optNode); });
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
}, '@VERSION@', {requires: ['node', 'event', 'anim'] });
