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

namespace mod_choicegroup\courseformat;

use core\activity_dates;
use core\output\action_link;
use core\output\local\properties\button;
use core\output\local\properties\text_align;
use core\output\pix_icon;
use core\url;
use core_calendar\output\humandate;
use core_courseformat\local\overview\overviewitem;
use mod_choicegroup\manager;

/**
 * Fair Allocation overview integration (for Moodle 5.1+)
 *
 * @package   mod_choicegroup
 * @copyright 2025 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /**
     * @var manager the choicegroup manager.
     */
    private manager $manager;
    /**
     * @var \core\output\renderer_helper $rendererhelper the renderer helper
     */
    private \core\output\renderer_helper $rendererhelper;

    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        \core\output\renderer_helper $rendererhelper
    ) {
        parent::__construct($cm);
        $this->rendererhelper = $rendererhelper;
        $this->manager = manager::create_from_coursemodule($cm);
    }

    /**
     * Get the rating begins at date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_timerestrictstart_overview(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);

        $opendate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeopen') {
                $opendate = $date['timestamp'];
                break;
            }
        }
        if (empty($opendate)) {
            return new overviewitem(
                name: get_string('choicebegins', 'choicegroup'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($opendate);

        return new overviewitem(
            name: get_string('choicebegins', 'choicegroup'),
            value: $opendate,
            content: $content,
        );
    }

    /**
     * Get the rating ends at date overview item.
     *
     * @return overviewitem|null
     * @throws \coding_exception
     */
    public function get_extra_timerestrictstop_overview(): ?overviewitem {
        global $USER;

        $dates = activity_dates::get_dates_for_module($this->cm, $USER->id);
        $closedate = null;
        foreach ($dates as $date) {
            if ($date['dataid'] === 'timeclose') {
                $closedate = $date['timestamp'];
                break;
            }
        }
        if (empty($closedate)) {
            return new overviewitem(
                name: get_string('choiceends', 'choicegroup'),
                value: null,
                content: '-',
            );
        }

        $content = humandate::create_from_timestamp($closedate);

        return new overviewitem(
            name: get_string('choiceends', 'choicegroup'),
            value: $closedate,
            content: $content,
        );
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'choice_begintime' => $this->get_extra_timerestrictstart_overview(),
            'choice_endtime' => $this->get_extra_timerestrictstop_overview(),
            'studentswhochose' => $this->get_extra_responses_overview(),
            'choice' => $this->get_extra_status_for_user(),
        ];
    }

    /**
     * Get the choice status overview item.
     *
     * @return overviewitem|null An overview item or null for teachers.
     */
    private function get_extra_status_for_user(): ?overviewitem {
        if (
            !has_capability('mod/choicegroup:choose', $this->cm->context) ||
            has_capability('mod/choicegroup:readresponses', $this->cm->context)
        ) {
            return null;
        }

        $status = $this->manager->has_answered();
        $statustext = get_string('notanswered', 'choicegroup');
        if ($status) {
            $statustext = get_string('answered', 'choicegroup');
        }
        $choicestatuscontent = "-";
        if ($status) {
            $choicestatuscontent = new pix_icon(
                pix: 'i/checkedcircle',
                alt: $statustext,
                component: 'core',
                attributes: ['class' => 'text-success'],
            );
        }
        return new overviewitem(
            name: get_string('choice', 'choicegroup'),
            value: $status,
            content: $choicestatuscontent,
            textalign: text_align::CENTER,
        );
    }

    /**
     * Retrieves an overview of responses for the choicegroup.
     *
     * @return overviewitem|null An overview item c, or null if the user lacks the required capability.
     */
    private function get_extra_responses_overview(): ?overviewitem {
        global $USER;

        if (!has_capability('mod/choicegroup:readresponses', $this->manager->get_coursemodule()->context)) {
            return null;
        }

        if (is_callable([$this, 'get_groups_for_filtering'])) {
            $groupids = array_keys($this->get_groups_for_filtering());
        } else {
            $groupids = [];
        }

        $submissions = $this->manager->count_all_users_answered($groupids);
        $total = $this->manager->count_all_users($groupids);

        if (
            class_exists(button::class) &&
            (new \ReflectionClass(button::class))->hasConstant('SECONDARY_OUTLINE')
        ) {
            if (
                class_exists(button::class) &&
                (new \ReflectionClass(button::class))->hasConstant('BODY_OUTLINE')
            ) {
                $buttonoutline = button::BODY_OUTLINE;
            } else {
                $buttonoutline = button::SECONDARY_OUTLINE;
            }
            $buttonclass = $buttonoutline->classes();
        } else {
            $buttonclass = "btn btn-outline-secondary";
        }

        $content = new action_link(
            url: new url('/mod/choicegroup/report.php', ['id' => $this->cm->id]),
            text: get_string(
                'count_of_total',
                'core',
                ['count' => $submissions, 'total' => $total]
            ),
            attributes: ['class' => $buttonclass],
        );

        return new overviewitem(
            name: get_string('choices', 'choicegroup'),
            value: $submissions,
            content: $content,
            textalign: text_align::CENTER,
        );
    }
}
