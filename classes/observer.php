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
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('mad_dashboard.php');

/**
 * Event observer.
 * Sends all the events to mad2 API
 *
 * @package   block_mad2api
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_mad2api_observer {
  /**
   * Sends the event to the mad2 API
   *
   * @param \core\event\base $event
  */
  public static function new_event(\core\event\base $event) {
    global $DB, $USER;

    $courseId = $event->courseid;

    if (!isset($USER) || !isset($courseId) || !\block_mad2api\mad_dashboard::is_course_enabled($courseId)) {
      return;
    }

    $url = "api/v2/courses/{$courseId}/events";

    $data = array(
      'event_name' => $event->eventname,
      'component' => $event->component,
      'target' => $event->target,
      'action' => $event->action,
      'moodle_id' => $courseId,
      'moodle_related_user_id' => $event->relateduserid,
      'moodle_user_id' => $event->userid,
      'other' => $event->other,
      "context_id" => $event->contextid,
      'raw_data' => \block_mad2api\mad_dashboard::camelizeObject($event),
      'time_created' => $event->timecreated
    );

    \block_mad2api\mad_dashboard::do_post_request($url, $data, $courseId);
  }

  /**
   * Sends the user enrollment event to the mad2 API
   *
   * @param \core\event\base $event
  */
  public static function new_user_enrolment_created(\core\event\base $event) {
    global $DB, $USER;

    $courseId = $event->courseid;

    if (!isset($USER) || !isset($courseId) || !\block_mad2api\mad_dashboard::is_course_enabled($courseId)) {
      return;
    }

    $url = "api/v2/courses/{$courseId}/events";

    $eventName = ($event->eventname == '\core\event\role_assigned') ? '\core\event\user_enrolment_created' : $event->eventname;

    print_object(\block_mad2api\mad_dashboard::get_user(
      $event->relateduserid, $courseId
    ));

    $data = array(
      'eventName' => $eventName,
      'component' => $event->component,
      'target' => $event->target,
      'action' => $event->action,
      'moodle_id' => $courseId,
      'moodleRelatedUserId' => $event->relateduserid,
      'moodleUserId' => $event->userid,
      "contextId" => $event->contextid,
      'rawData' => \block_mad2api\mad_dashboard::camelizeObject($event),
      'timeCreated' => $event->timecreated,
      'other' => \block_mad2api\mad_dashboard::get_user(
        $event->relateduserid, $courseId
      )
    );

    \block_mad2api\mad_dashboard::do_post_request($url, $data, $courseId);
  }
}
