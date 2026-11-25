<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Scheduled task to send the course attendance.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mad2api\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Sends the current attendance of every monitored course.
 *
 * Kept apart from the other tasks so that an attendance failure only delays
 * attendance, never logs, students or the resend check.
 */
class mad_send_attendance extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_attendance_task_name', 'block_mad2api');
    }

    /**
     * Execute the task.
     *
     * Throws when any API call failed so that Moodle marks the run as failed
     * and backs this task off.
     *
     * @throws \moodle_exception When at least one API call failed.
     */
    public function execute() {
        global $DB;

        $records = $DB->get_records('block_mad2api_dash_settings', ['isenabled' => 1]);
        $failures = 0;

        mtrace("Sending attendance for " . count($records) . " courses \n");

        foreach ($records as $record) {
            $transportfailures = \block_mad2api\mad_dashboard::transport_failures();

            // One failing course must not abort the run for the remaining ones...
            if (!\block_mad2api\mad_dashboard::api_send_attendance((int)$record->courseid)) {
                $failures++;
            }

            // ...unless the API is not answering at all.
            if (\block_mad2api\mad_dashboard::transport_failures() > $transportfailures) {
                mtrace("API unreachable, skipping the remaining courses in this run \n");

                break;
            }
        }

        if ($failures > 0) {
            throw new \moodle_exception('task_api_failures', 'block_mad2api', '', $failures);
        }
    }
}
