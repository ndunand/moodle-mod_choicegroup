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
 * The mod_choicegroup course module viewed event.
 *
 * @package    mod_choicegroup
 * @copyright  2014 Université de Lausanne
 * @author     Nicolas Dunand <Nicolas.Dunand@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_choicegroup\event;

/**
 * Event for when a choicegroup report is viewed.
 */
class report_viewed extends \core\event\course_module_viewed {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'choicegroup';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $a = new \stdClass();
        $a->userid = $this->userid;
        $a->contextinstanceid = $this->contextinstanceid;
        return get_string('event:reportviewed_desc', 'mod_choicegroup', $a);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:reportviewed', 'mod_choicegroup');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/choicegroup/view.php', ['id' => $this->contextinstanceid]);
    }
}
