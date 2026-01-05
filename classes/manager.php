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

namespace mod_choicegroup;

use cm_info;
use context_module;
use stdClass;

/**
 * Class manager for choicegroup activity
 *
 * @package   mod_choicegroup
 * @copyright 2025 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** Module name. */
    public const MODULE = 'choicegroup';

    /** @var context_module the current context. */
    private $context;

    /** @var stdClass $course record. */
    private $course;

    /** @var \moodle_database the database instance. */
    private \moodle_database $db;

    /**
     * Class constructor.
     *
     * @param cm_info $cm course module info object
     * @param stdClass $instance activity instance object.
     */
    public function __construct(
        /** @var cm_info $cm the given course module info */
        private cm_info $cm,
        /** @var stdClass $instance activity instance object */
        private stdClass $instance
    ) {
        $this->context = context_module::instance($cm->id);
        $this->db = \core\di::get(\moodle_database::class);
        $this->course = $cm->get_course();
    }

    /**
     * Create a manager instance from an instance record.
     *
     * @param stdClass $instance an activity record
     * @return manager
     */
    public static function create_from_instance(stdClass $instance): self {
        $cm = get_coursemodule_from_instance(self::MODULE, $instance->id);
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        return new self($cm, $instance);
    }

    /**
     * Create a manager instance from a course_modules record.
     *
     * @param stdClass|cm_info $cm an activity record
     * @return manager
     */
    public static function create_from_coursemodule(stdClass|cm_info $cm): self {
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        $db = \core\di::get(\moodle_database::class);
        $instance = $db->get_record(self::MODULE, ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($cm, $instance);
    }

    /**
     * Return the current context.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->context;
    }

    /**
     * Return the current instance.
     *
     * @return stdClass the instance record
     */
    public function get_instance(): stdClass {
        return $this->instance;
    }

    /**
     * Return the current cm_info.
     *
     * @return cm_info the course module
     */
    public function get_coursemodule(): cm_info {
        return $this->cm;
    }

    /**
     * Check if the current user has responded in the choicegroup.
     *
     * @return bool true if the user has answered, false otherwise
     */
    public function has_answered(): bool {
        global $USER;
        $answer = \choicegroup_get_user_answer($this->instance, $USER->id);
        return !empty($answer);
    }

    /**
     * Return the count of users who can submit ratings to this choicegroup module, that the current user can see.
     *
     * @param int[] $groupids the group identifiers to filter by, empty array means no filtering
     * @return int the number of answers that the user can see
     */
    public function count_all_users(
        array $groupids = [],
    ): int {
        if (!has_capability('mod/choicegroup:choose', $this->context)) {
            return 0;
        }

        // Get all users with the capability to choose in this context.
        $users = get_users_by_capability($this->context, 'mod/choicegroup:choose', 'u.id');

        if (empty($users)) {
            return 0;
        }

        // No group filtering requested: simply count users with the capability.
        if (empty($groupids)) {
            return count($users);
        }

        // With group filtering: count users that are in any of the requested groups
        // or that are not in any group (group 0 semantics).
        $groupids = array_unique($groupids);
        $count = 0;
        foreach ($users as $userid => $unused) {
            $usergroups = groups_get_user_groups($this->course->id, $userid);
            $ug = isset($usergroups[0]) ? $usergroups[0] : [];
            if (!empty(array_intersect($ug, $groupids)) || empty($ug)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Return the current count of users who have submitted ratings to this choicegroup module, that the current user can see.
     *
     * @param int[] $groupids the group identifiers to filter by, empty array means no filtering
     * @return int the number of answers that the user can see
     */
    public function count_all_users_answered(
        array $groupids = [],
    ): int {
        if (!has_capability('mod/choicegroup:readresponses', $this->context)) {
            return 0;
        }
        // Check to see if groups are being used in this choicegroup.
        $groupmode = groups_get_activity_groupmode($this->cm);

        if ($groupmode) {
            groups_get_activity_group($this->cm, true);
        }

        // Big function, approx 6 SQL calls per user.
        $allresponses = choicegroup_get_response_data($this->instance, $this->cm, $groupmode, 'no');
        $responsecount = 0;
        $respondents = [];
        foreach ($allresponses as $optionid => $userlist) {
            if ($optionid) {
                $responsecount += count($userlist);
                if ($choicegroup->multipleenrollmentspossible) {
                    foreach ($userlist as $user) {
                        if (!in_array($user->id, $respondents)) {
                            $respondents[] = $user->id;
                        }
                    }
                }
            }
        }
        return $responsecount;
    }
}
