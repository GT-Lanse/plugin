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
        global $DB;

        $ch = curl_init();
        $api_url = get_config('mad2api', 'api_url');
        $url = "{$api_url}/api/plugin/courses/{$event->courseid}/events";
        $api_key = get_config('mad2api', 'api_key');
        $related_user = $DB->get_record('user', array('id' => $event->relateduserid));
        $user = $DB->get_record('user', array('id' => $event->userid));
        $data = array(
          'event_name' => end(explode("\\", $event->eventname)),
          'course_id' => $event->courseid,
          'related_user' => $related_user,
          'user' => $user,
          'other' => $event->other
        );
        $headers = [
          'accept: application/json',
          'Content-Type: application/json',
          "API-KEY: {$api_key}"
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  //Post Fields
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_close($ch);
    }
}
