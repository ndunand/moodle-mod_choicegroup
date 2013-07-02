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


/**
* @namespace
*/
M.mod_choicegroup = M.mod_choicegroup || {};

/**
* This function is initialized from PHP
*
* @param {Object} Y YUI instance
*/
M.mod_choicegroup.init = function(Y) {

    this.Y = Y;

    // The button on which the fill-in begins
    var the_button = Y.one("#id_setlimit"),
        the_items = Y.all('input.mod-choicegroup-limit-input');

    // On click fill in the limit in every field
    the_button.on('click', function (e) {
        // Get the value string
        var text_value = Y.one("#id_generallimitation").get('value');
        // Make sure we've got an integer value
        var int_value = parseInt(text_value);
        if (!isNaN(int_value)) {
            // Set all new values
            the_items.set('value', int_value);
        }
    });

}

