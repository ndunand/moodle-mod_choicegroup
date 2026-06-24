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
 * Choice group settings JavaScript module
 *
 * @copyright  2025 UNIL
 * @author     Pierre Guarnieri <pierre.guarnieri@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(function() {

    return {
        init: function(param) {

            const availableSelect = document.getElementById('availablegroups');
            const selectedSelect = document.getElementById('id_selectedGroups');
            const addBtn = document.getElementById('addGroupButton');
            const removeBtn = document.getElementById('removeGroupButton');
            const expandAllBtn = document.getElementById('expandButton');
            const collapseAllBtn = document.getElementById('collapseButton');
            const form = document.getElementById(param.formid);
            const serializedselectedgroups = document.getElementById('serializedselectedgroups');
            const ARROW_DOWN = '\u25BC';
            const ARROW_RIGHT = '\u25B6';
            const removedOptionsMap = new Map(); // Used to store options states when toggle grouping
            let sortBy = param.sortgroupsby;

            const enableDisableLimit = document.getElementById('id_limitanswers');
            const globalLimitInput = document.getElementById('id_generallimitation');
            const applyLimitToAllGroups = document.getElementById('id_setlimit');

            const limitDiv = document.getElementById('fitem_id_limit_0');
            const limitLabel = document.getElementById('label_for_limit_ui');
            const limitInput = document.getElementById('ui_limit_input');
            const limitHiddenInputs = document.getElementsByClassName('limit_input_node');
            let limitGroup = null;

            if (!availableSelect || !selectedSelect || !serializedselectedgroups || !applyLimitToAllGroups) {
                return;
            }

            // Store original indexes states
            Array.from(availableSelect.options).forEach((opt, index) => {
                opt.dataset.originalIndex = index;
            });

            // On form load
            if (serializedselectedgroups.value !== '') {
                let selectedGroups = serializedselectedgroups.value.split(';').filter(n => n !== '');
                let inSelected = [];

                Array.from(availableSelect.options).forEach(opt => {
                    if (opt.classList.contains('group') && !inSelected.includes(opt.value)) {
                        opt.selected = selectedGroups.includes(opt.value);
                        inSelected.push(opt.value);
                    }
                });

                moveToSelected();
            }

            // Check if elements exist
            if (!availableSelect || !selectedSelect) {
                return;
            }

            let groupingStates = {};

            // Initialize grouping states
            Array.from(availableSelect.options).forEach(opt => {
                if (opt.classList.contains('grouping')) {
                    groupingStates[opt.value] = true;
                }
            });

            // Setup event listeners
            setupEventListeners();

            /**
             * Setup Events
             */
            function setupEventListeners() {
                availableSelect.addEventListener('dblclick', function(e) {
                    const clickedIndex = e.currentTarget.selectedIndex;
                    if (clickedIndex >= 0) {
                        const option = this.options[clickedIndex];
                        if (option && option.classList.contains('grouping')) {
                            e.preventDefault();
                            toggleGrouping(option);
                        } else if (option && option.classList.contains('group')) {
                            e.preventDefault();
                            moveToSelected();
                        }
                    }
                });

                selectedSelect.addEventListener('dblclick', function(e) {
                    const clickedIndex = e.currentTarget.selectedIndex;
                    limitDiv.style.display = 'none';
                    limitInput.disabled = true;
                    if (clickedIndex >= 0) {
                        e.preventDefault();
                        moveToAvailable();
                    }
                });

                if (addBtn) {
                    addBtn.addEventListener('click', moveToSelected);
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', moveToAvailable);
                }

                if (expandAllBtn) {
                    expandAllBtn.addEventListener('click', expandAll);
                }

                if (collapseAllBtn) {
                    collapseAllBtn.addEventListener('click', collapseAll);
                }

                form.addEventListener('submit', function(e) {
                    if (selectedSelect.options.length < 1 && !window.skipClientValidation) {
                        alert(M.util.get_string('pleaseselectonegroup', 'choicegroup'));
                        e.preventDefault();
                        e.stopPropagation();
                        return;
                    }

                    let serializedSelection = '';
                    selectedSelect.options.forEach(option => {
                        serializedSelection += ';' + option.value;
                    });

                    serializedselectedgroups.value = serializedSelection;
                });

                applyLimitToAllGroups.addEventListener('click', applyLimit);

                ['click', 'change'].forEach(event => {
                    selectedSelect.addEventListener(event, function (e) {
                        let selectedOptions = Array.from(selectedSelect.selectedOptions);
                        if (selectedOptions.length === 1 && enableDisableLimit.value === '1') {
                            e.preventDefault();
                            let option = selectedSelect.options[selectedSelect.selectedIndex];
                            limitGroup = option;
                            limitDiv.style.display = 'block';
                            limitLabel.textContent = M.util.get_string('set_limit_for_group', 'choicegroup')
                                + ' ' + getGroupNameWithoutLimitText(option) + ":";
                            limitInput.disabled = false;
                            limitInput.value = getLimitOfSelectedGroup(option).value;
                        } else {
                            limitGroup = null;
                            limitDiv.style.display = 'none';
                        }
                    });
                });

                ['input', 'blur', 'change'].forEach(event =>
                    limitInput.addEventListener(event, function(e) {
                        e.preventDefault();
                        if (!limitGroup) {
                            return;
                        }
                        if (isNaN(limitInput.value)) {
                            limitInput.value = 0;
                            alert(M.util.get_string('the_value_you_entered_is_not_a_number', 'choicegroup'));
                            return;
                        }

                        getLimitOfSelectedGroup(limitGroup).value = limitInput.value;
                        updateLimitOfSelectedGroup(limitGroup);

                        if (event !== 'input') {
                            limitDiv.style.display = 'none';
                        }
                    })
                );

                enableDisableLimit.addEventListener('change', function(e) {
                    e.preventDefault();
                    if (enableDisableLimit.value === '1') {
                        let option = selectedSelect.options[selectedSelect.selectedIndex];
                        if (option) {
                            limitGroup = option;
                            limitDiv.style.display = 'block';
                        }
                        updateLimitOfAllSelectedGroups();
                    } else {
                        limitGroup = null;
                        limitDiv.style.display = 'none';
                        clearLimitOfAllGroups(selectedSelect);
                    }
                });
            }

            /**
             * Handle grouping option expansion or movement.
             * @param {HTMLOptionElement} groupingOption - The grouping option element.
             */
            function toggleGrouping(groupingOption) {
                const groupingValue = groupingOption.value;
                groupingStates[groupingValue] = !groupingStates[groupingValue];

                const isExpanded = groupingStates[groupingValue];
                const currentText = groupingOption.textContent;
                groupingOption.textContent = currentText.replace(/^[\u25BC\u25B6]/, isExpanded ? ARROW_DOWN : ARROW_RIGHT);

                if (isExpanded) {
                    const removedOptions = removedOptionsMap.get(groupingValue);
                    if (removedOptions && removedOptions.length > 0) {
                        const groupingIndex = Array.from(availableSelect.options).indexOf(groupingOption);
                        removedOptions.forEach((option, i) => {
                            const insertPosition = groupingIndex + 1 + i;
                            if (insertPosition < availableSelect.options.length) {
                                availableSelect.add(option, insertPosition);
                            } else {
                                availableSelect.add(option);
                            }
                        });

                        removedOptionsMap.delete(groupingValue);
                    }
                } else {
                    const groupingIndex = Array.from(availableSelect.options).indexOf(groupingOption);
                    const optionsToRemove = [];
                    let nextIndex = groupingIndex + 1;

                    while (nextIndex < availableSelect.options.length) {
                        const nextOption = availableSelect.options[nextIndex];

                        if (nextOption.classList.contains('grouping')) {
                            break;
                        }

                        if (nextOption.classList.contains('nested')) {
                            optionsToRemove.push(nextOption);
                        }

                        nextIndex++;
                    }

                    for (let i = optionsToRemove.length - 1; i >= 0; i--) {
                        optionsToRemove[i].remove();
                    }

                    removedOptionsMap.set(groupingValue, optionsToRemove);
                }
            }

            /**
             * Expand all groupings
             */
            function expandAll() {
                const groupingOptions = Array.from(availableSelect.options).filter(opt =>
                    opt.classList.contains('grouping')
                );

                groupingOptions.forEach(opt => {
                    if (!groupingStates[opt.value]) {
                        toggleGrouping(opt);
                    }
                });
            }

            /**
             * Collapse all groupings
             */
            function collapseAll() {
                const groupingOptions = Array.from(availableSelect.options).filter(opt =>
                    opt.classList.contains('grouping')
                );

                groupingOptions.forEach(opt => {
                    if (groupingStates[opt.value]) {
                        toggleGrouping(opt);
                    }
                });
            }

            /**
             * Move to Selected Groups
             */
            function moveToSelected() {
                const data = [];
                const usedValues = [];

                Array.from(availableSelect.selectedOptions).forEach(selectedOpt => {
                    if (selectedOpt.classList.contains('group') && !usedValues.includes(selectedOpt.value)) {
                        const options = findAllOptions(availableSelect, selectedOpt.value, 'group');
                        const originalIndexes = options.map(o => Number(o.dataset.originalIndex));

                        selectedOpt.dataset.originalIndexes = JSON.stringify(originalIndexes);
                        usedValues.push(selectedOpt.value);
                        data.push(selectedOpt);
                    }
                });

                data.forEach(opt => {
                    selectedSelect.appendChild(opt);
                    opt.selected = false;
                    findAllOptions(availableSelect, opt.value, 'group').forEach(optToRemove => optToRemove.remove());
                });

                const sortedOptions = Array.from(selectedSelect.options).sort((a, b) => {
                    if (sortBy === 'name') {
                        return a.text.localeCompare(b.text);
                    } else if (sortBy === 'timecreated') {
                        // Assumes timecreated is stored as a data attribute
                        const timeA = Number(a.dataset.timecreated || 0);
                        const timeB = Number(b.dataset.timecreated || 0);
                        return timeA - timeB;
                    }
                    return 0;
                });

                sortedOptions.forEach(opt => {
                    selectedSelect.appendChild(opt);
                });

                if (enableDisableLimit.value === '1') {
                    updateLimitOfAllSelectedGroups();
                }
            }

            /**
             * Update limit for all selected groups
             */
            function updateLimitOfAllSelectedGroups() {
                selectedSelect.forEach(opt => {
                    updateLimitOfSelectedGroup(opt);
                });
            }

            /**
             * Update limit for one selected group
             * @param {HTMLOptionElement} group
             */
            function updateLimitOfSelectedGroup(group) {
                const limitEl = getLimitOfSelectedGroup(group);
                if (limitEl) {
                    group.text = getGroupNameWithoutLimitText(group) + '(' + (getLimitOfSelectedGroup(group).value || 0) + ')';
                }
            }

            /**
             * @param {HTMLOptionElement} group
             * @returns {string}
             */
            function getGroupNameWithoutLimitText(group) {
                var indexOfLimitText = group.text.indexOf('(');
                if (indexOfLimitText !== -1) {
                    return group.text.substring(0, indexOfLimitText);
                } else {
                    return group.text;
                }
            }

            /**
             * @param {HTMLOptionElement} group
             * @returns {HTMLInputElement}
             */
            function getLimitOfSelectedGroup(group) {
                return document.getElementById('group_' + group.value + '_limit');
            }

            /**
             * Apply global group limit
             */
            function applyLimit() {
                let generalLimitValue = parseInt(globalLimitInput.value);

                if (!isNaN(generalLimitValue)) {
                    limitHiddenInputs.forEach(limit => {
                        limit.value = generalLimitValue;
                    });
                } else {
                    alert(M.util.get_string('the_value_you_entered_is_not_a_number', 'choicegroup'));
                }

                updateLimitOfAllSelectedGroups();
            }

            /**
             * Clear limit for all selected groups
             * @param {HTMLSelectElement} select
             */
            function clearLimitOfAllGroups(select) {
                select.forEach(opt => {
                    clearLimitOfGroup(opt);
                });
            }

            /**
             * Clear limit for one selected group
             * @param {HTMLOptionElement} group
             */
            function clearLimitOfGroup(group) {
                limitDiv.style.display = 'none';
                group.text = getGroupNameWithoutLimitText(group);
            }

            /**
             * Move to Available groups
             */
            function moveToAvailable() {
                const toRestore = [];

                Array.from(selectedSelect.selectedOptions).forEach(opt => {
                    const originalIndexes = JSON.parse(opt.dataset.originalIndexes || `[${opt.dataset.originalIndex || -1}]`);
                    opt.remove();

                    originalIndexes.forEach((origIndex, idx) => {
                        const newOpt = idx === 0 ? opt : opt.cloneNode(true);
                        newOpt.removeAttribute('id');
                        newOpt.dataset.originalIndex = String(origIndex);
                        newOpt.dataset.originalIndexes = JSON.stringify(originalIndexes);

                        toRestore.push({ index: Number(origIndex), option: newOpt });
                    });
                });

                toRestore.sort((a, b) => a.index - b.index);

                toRestore.forEach(({ index, option }) => {
                    const nextOpt = Array.from(availableSelect.options).find(o => Number(o.dataset.originalIndex) > index);
                    if (nextOpt) {
                        availableSelect.add(option, nextOpt);
                    } else {
                        availableSelect.add(option);
                    }
                    option.selected = false;
                });

                clearLimitOfAllGroups(availableSelect);
            }

            /**
             * Find all <option> elements in a select that match a given value and optional class.
             * @param {HTMLSelectElement} selectElement - The select element to search within.
             * @param {string} value - The option value to match.
             * @param {string|null} [className=null] - Optional class name to filter by.
             * @returns {HTMLOptionElement[]} The matching option elements.
             */
            function findAllOptions(selectElement, value, className = null) {
                return Array.from(selectElement.options).filter(opt => {
                    return opt.value === value && (!className || opt.classList.contains(className));
                });
            }
        }
    };
});
