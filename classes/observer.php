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
 * Event observer.
 *
 * @package   block_mad2api
 * @copyright 2022
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('mad_dashboard.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/weblib.php');

/**
 * Event observer.
 * Sends all the events to mad2 API.
 *
 * @package   block_mad2api
 */
class block_mad2api_observer {

    /**
     * Sends the event to the mad2 API.
     *
     * @param \core\event\base $event
     */
    public static function new_event(\core\event\base $event) {
        global $DB, $USER;

        $courseid = $event->courseid;

        if (!isset($USER) || !isset($courseid) || !\block_mad2api\mad_dashboard::is_course_enabled($courseid)) {
            return;
        }

        $url = "api/v2/courses/{$courseid}/events";
        $other = $event->other;

        if (
            $event->eventname === '\core\event\course_module_updated' ||
            $event->eventname === '\core\event\grade_item_updated' ||
            $event->eventname === '\core\event\course_module_created'
        ) {
            // Using placeholder to fetch the course module securely
            $coursemodule = $DB->get_record('course_modules', ['id' => $event->objectid], '*', MUST_EXIST);
            $cm = get_coursemodule_from_id(false, $event->objectid, 0, false, MUST_EXIST);

            $grades = grade_get_grades(
                $coursemodule->course, 'mod', $cm->modname, $cm->instance
            );

            $activityurl = new \moodle_url("/mod/{$cm->modname}/view.php", ['id' => $cm->id]);

            $other['visible'] = $coursemodule->visible;

            if ($event->eventname !== '\core\event\grade_item_updated') {
                $other['gradable'] = !empty($grades->items);
            }

            $other['duedate'] = \block_mad2api\mad_dashboard::get_activity_duedate($cm);
            $other['url'] = $activityurl->out();
        }

        $data = [
            'event_name' => $event->eventname,
            'component' => $event->component,
            'target' => $event->target,
            'action' => $event->action,
            'moodle_id' => $courseid,
            'moodle_related_user_id' => $event->relateduserid,
            'moodle_user_id' => $event->userid,
            'other' => $other,
            'context_id' => $event->contextid,
            'raw_data' => \block_mad2api\mad_dashboard::camelizeObject($event),
            'time_created' => $event->timecreated,
        ];

        \block_mad2api\mad_dashboard::do_post_request($url, $data, $courseid);
    }

    /**
     * Sends the new grade event to the mad2 API.
     *
     * @param \core\event\base $event
     */
    public static function new_grade(\core\event\base $event) {
        global $DB, $USER;

        $courseid = $event->courseid;

        if (!isset($USER) || !isset($courseid) || !\block_mad2api\mad_dashboard::is_course_enabled($courseid)) {
            return;
        }

        $url = "api/v2/courses/{$courseid}/events";
        $other = $event->other;
        $itemid = $other['itemid'];

        $gradeitem = $DB->get_record('grade_items', ['id' => $itemid]);

        if ($gradeitem && $gradeitem->itemtype === 'mod') {
            $other['instance_id'] = $gradeitem->iteminstance;
            $other['item_module'] = $gradeitem->itemmodule;
        }

        $data = [
            'event_name' => $event->eventname,
            'component' => $event->component,
            'target' => $event->target,
            'action' => $event->action,
            'moodle_id' => $courseid,
            'moodle_related_user_id' => $event->relateduserid,
            'moodle_user_id' => $event->userid,
            'other' => $other,
            'context_id' => $event->contextid,
            'raw_data' => \block_mad2api\mad_dashboard::camelizeObject($event),
            'time_created' => $event->timecreated,
        ];

        \block_mad2api\mad_dashboard::do_post_request($url, $data, $courseid);
    }

    /**
     * Sends the user enrollment event to the mad2 API.
     *
     * @param \core\event\base $event
     */
    public static function new_user_enrolment_created(\core\event\base $event) {
        global $USER;

        $courseid = $event->courseid;

        if (!isset($USER) || !isset($courseid) || !\block_mad2api\mad_dashboard::is_course_enabled($courseid)) {
            return;
        }

        $url = "api/v2/courses/{$courseid}/events";

        $eventname = ($event->eventname === '\core\event\role_assigned')
            ? '\core\event\user_enrolment_created'
            : $event->eventname;

        $otherdata = \block_mad2api\mad_dashboard::get_user(
            $event->relateduserid, $courseid
        );

        $data = [
            'eventName' => $eventname,
            'component' => $event->component,
            'target' => $event->target,
            'action' => $event->action,
            'moodle_id' => $courseid,
            'moodleRelatedUserId' => $event->relateduserid,
            'moodleUserId' => $event->userid,
            'contextId' => $event->contextid,
            'rawData' => \block_mad2api\mad_dashboard::camelizeObject($event),
            'timeCreated' => $event->timecreated,
            'other' => $otherdata,
        ];

        \block_mad2api\mad_dashboard::do_post_request($url, $data, $courseid);
    }

    /**
     * Sends the updated user event to the mad2 API.
     *
     * @param \core\event\base $event
     */
    public static function user_updated(\core\event\base $event) {
        $course = \block_mad2api\mad_dashboard::enrolled_monitored_courses($event->relateduserid);

        if (!isset($course)) {
            return;
        }

        $url = "api/v2/courses/{$course->id}/events";

        $data = [
            'eventName' => $event->eventname,
            'component' => $event->component,
            'target' => $event->target,
            'action' => $event->action,
            'moodle_id' => $course->id,
            'moodleRelatedUserId' => $event->relateduserid,
            'moodleUserId' => $event->userid,
            'contextId' => $event->contextid,
            'rawData' => \block_mad2api\mad_dashboard::camelizeObject($event),
            'timeCreated' => $event->timecreated,
            'other' => \block_mad2api\mad_dashboard::get_user(
                $event->relateduserid, $course->id
            ),
        ];

        \block_mad2api\mad_dashboard::do_post_request($url, $data, $course->id);
    }
}
