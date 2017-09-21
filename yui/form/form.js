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
			FORM: '#mform1',
			APPLY_LIMIT_TO_ALL_GRPS_BTN: '#id_setlimit',
			ENABLE_DISABLE_LIMITING_SELECT: '#id_limitanswers',
			GLOBAL_LIMIT_INPUT: '#id_generallimitation',
			HIDDEN_LIMIT_INPUTS: 'input.limit_input_node',
	};
	Y.namespace('Moodle.mod_choicegroup.form');
	Y.Moodle.mod_choicegroup.form = {
			init: function() {

				// -------------------------------
				// Global Variables
				// -------------------------------
                var CHAR_LIMITUI_PAR_LEFT = M.util.get_string('char_limitui_parenthesis_start', 'choicegroup');
                var CHAR_LIMITUI_PAR_RIGHT = M.util.get_string('char_limitui_parenthesis_end', 'choicegroup');

				var formNode = Y.one(SELECTORS.FORM);
				var applyLimitToAllGroupsButtonNode = Y.one(SELECTORS.APPLY_LIMIT_TO_ALL_GRPS_BTN);
				var limitAnswersSelectNode = Y.one(SELECTORS.ENABLE_DISABLE_LIMITING_SELECT);

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
					var optionsNodes = Y.all(SELECTORS.SELECTED_GRPS_SELECT + " option");
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

                function getGroupNameWithoutLimitText(groupNode) {
                    var indexOfLimitUIText = groupNode.get('text').indexOf(' ' + CHAR_LIMITUI_PAR_LEFT);
                    if (indexOfLimitUIText !== -1) {
                        return groupNode.get('text').substring(0, indexOfLimitUIText);
                    } else {
                        return groupNode.get('text');
                    }
                }
                function clearLimitUIFromSelectedGroup(groupNode) {
                	groupNode.set('text', getGroupNameWithoutLimitText(groupNode));
                }

                function updateLimitUIOfSelectedGroup(groupNode) {
                    groupNode.set('text', getGroupNameWithoutLimitText(groupNode) + ' ' + CHAR_LIMITUI_PAR_LEFT + getInputLimitNodeOfSelectedGroupNode(groupNode).get('value') + CHAR_LIMITUI_PAR_RIGHT);
                }

                function updateLimitUIOfAllSelectedGroups() {
                    Y.all(SELECTORS.SELECTED_GRPS_SELECT + " option").each(function(optNode) { updateLimitUIOfSelectedGroup(optNode); });
                }

                function clearLimitUIFromAllSelectedGroups() {
                    Y.all(SELECTORS.SELECTED_GRPS_SELECT + " option").each(function(optNode) { clearLimitUIFromSelectedGroup(optNode); });
                }

                function expandOrCollapseGrouping(groupingNode) {
					if (((typeof groupingsNodesContainer[groupingNode.get('value')]) == 'undefined') || ( groupingsNodesContainer[groupingNode.get('value')].length == 0)) {
						collapseGrouping(groupingNode);
						expandButtonNode.set('disabled', false);
					} else {
						expandGrouping(groupingNode);
						collapseButtonNode.set('disabled', false);
					}
                }

                getTextWidth = function(text, font) {
                	// Thanks for http://stackoverflow.com/a/21015393/3430277
                    // re-use canvas object for better performance
                    var canvas = getTextWidth.canvas || (getTextWidth.canvas = document.createElement("canvas"));
                    var context = canvas.getContext("2d");
                    context.font = font;
                    var metrics = context.measureText(text);
                    return metrics.width;
                };

                function wasFirstCharacterClicked(e, n) {
                	// Thanks for http://stackoverflow.com/a/21015393/3430277
                	// e is the event, n is the node to check
					var style = window.getComputedStyle(n.getDOMNode(), null).getPropertyValue('font');
					if ((e.pageX - e.currentTarget.getX()) <= getTextWidth(n.get('text').charAt(0),style)) {
						return true;
					}
					return false;
                }

                // If necessary update their limit information
				if (limitAnswersSelectNode.get('value') == '1') { // limiting is enabled, show limit box
                    updateLimitUIOfAllSelectedGroups();
                }


				// ---------------------------------
				// Setup UI Bindings (on load)
				// ---------------------------------

				// On click fill in the limit in every field
				applyLimitToAllGroupsButtonNode.on('click', function (e) {
					// Get the value string
					var generalLimitValue = Y.one(SELECTORS.GLOBAL_LIMIT_INPUT).get('value');
					// Make sure we've got an integer value
					generalLimitValue = parseInt(generalLimitValue);
					if (!isNaN(generalLimitValue)) {
						var limitInputNodes = Y.all(SELECTORS.HIDDEN_LIMIT_INPUTS);
						limitInputNodes.each(function(n) { n.set('value', generalLimitValue); });
					} else {
						alert(M.util.get_string('the_value_you_entered_is_not_a_number', 'choicegroup'));
					}
                    updateLimitUIOfAllSelectedGroups();
				});

				limitAnswersSelectNode.on('change', function(e) {
					if (limitAnswersSelectNode.get('value') == '1') { // limiting is enabled, show limit box
                        updateLimitUIOfAllSelectedGroups();
					} else { // limiting is disabled
                        clearLimitUIFromAllSelectedGroups();
					}

				});


			},


	};
}, '@VERSION@', {requires: ['node', 'event'] });
