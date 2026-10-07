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
 * Scheduled task to check resend data.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mad2api\task;

defined('MOODLE_INTERNAL') || die();

class mad_check_resend_data extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('check_resend_data_task_name', 'block_mad2api');
    }

    /**
     * Checks every monitored course and sends pending activity names.
     *
     * Throws when any API call failed so that Moodle marks the run as failed:
     * the core fail delay then backs the task off while the API is down, and
     * the run log is kept even when task_logmode only records failures.
     *
     * @throws \moodle_exception When at least one API call failed.
     */
    public function execute() {
        global $DB;

        $records = $DB->get_records('block_mad2api_dash_settings', ['isenabled' => 1]);
        $failures = 0;
        $unreachable = false;

        mtrace("Checking resend data for " . count($records) . " courses \n");

        foreach ($records as $record) {
            mtrace("Checking resend for course #" . $record->courseid . "\n");

            $transportfailures = \block_mad2api\mad_dashboard::transport_failures();

            // One failing course must not abort the run for the remaining ones...
            if (!\block_mad2api\mad_dashboard::check_data_on_api((int)$record->courseid)) {
                $failures++;
            }

            // ...unless the API is not answering at all: every further course
            // would only burn the full request timeout.
            if (\block_mad2api\mad_dashboard::transport_failures() > $transportfailures) {
                $unreachable = true;

                mtrace("API unreachable, skipping the remaining courses in this run \n");

                break;
            }
        }

        if ($unreachable) {
            mtrace("API unreachable, skipping the pending activities check \n");
        } else {
            mtrace("Checking pending activities \n");

            if (!\block_mad2api\mad_dashboard::send_pending_activities()) {
                mtrace("Pending activities check finished with API errors \n");

                $failures++;
            }
        }

        if ($failures > 0) {
            throw new \moodle_exception('task_api_failures', 'block_mad2api', '', $failures);
        }
    }
}
