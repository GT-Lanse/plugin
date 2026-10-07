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
 * Mad logger scheduled task.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mad2api\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Sends enrolled students and course logs for every course awaiting delivery.
 */
class mad_logger extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_logs_task_name', 'block_mad2api');
    }

    /**
     * Execute the task.
     *
     * Throws when any API call failed so that Moodle marks the run as failed:
     * the core fail delay then backs the task off while the API is down, and
     * the run log is kept even when task_logmode only records failures.
     *
     * @throws \moodle_exception When at least one API call failed.
     */
    public function execute() {
        global $DB;

        list($statussql, $statusparams) = $DB->get_in_or_equal(
            ['todo', 'wip', 'error'],
            SQL_PARAMS_NAMED,
            'status'
        );

        $records = $DB->get_records_select(
            'block_mad2api_course_logs',
            "status {$statussql}",
            $statusparams
        );

        $failures = 0;

        foreach ($records as $record) {
            $transportfailures = \block_mad2api\mad_dashboard::transport_failures();

            // One failing course must not abort the run for the remaining ones...
            $sent = \block_mad2api\mad_dashboard::guard(function () use ($record) {
                return $this->send_course_data($record);
            }, 'mad_logger for course #' . (int)$record->courseid, false);

            if (!$sent) {
                $failures++;
            }

            // ...unless the API is not answering at all: every further course
            // would only burn the full request timeout.
            if (\block_mad2api\mad_dashboard::transport_failures() > $transportfailures) {
                mtrace("API unreachable, skipping the remaining courses in this run \n");

                break;
            }
        }

        if ($failures > 0) {
            throw new \moodle_exception('task_api_failures', 'block_mad2api', '', $failures);
        }
    }

    /**
     * Sends students and logs for a single course log record.
     *
     * @param \stdClass $record The block_mad2api_course_logs record.
     * @return bool True when nothing had to be sent or everything was accepted by the API.
     */
    private function send_course_data($record) {
        global $DB;

        if (!\block_mad2api\mad_dashboard::is_course_enabled((int)$record->courseid)) {
            mtrace("Skipping course #" . $record->courseid . " because monitoring is disabled.\n");

            return true;
        }

        $data = array(
            'id' => $record->id,
            'courseid' => $record->courseid,
            'updatedat' => date('Y-m-d H:i:s'),
            'status' => 'wip'
        );

        mtrace("Sending data from course #" . $record->courseid . "\n");

        $DB->update_record('block_mad2api_course_logs', $data);

        mtrace("course log updated to wip \n");

        mtrace("sending students \n");
        $studentssent = \block_mad2api\mad_dashboard::api_send_students($record->courseid);

        mtrace("sending logs \n");
        $logssent = \block_mad2api\mad_dashboard::api_send_logs($record->courseid);

        if (!$studentssent || !$logssent) {
            mtrace("Failed to send all data for course #" . $record->courseid . ". Updating status to error.\n");

            $data = array(
                'id' => $record->id,
                'courseid' => $record->courseid,
                'updatedat' => date('Y-m-d H:i:s'),
                'status' => 'error'
            );

            $DB->update_record('block_mad2api_course_logs', $data);

            return false;
        }

        mtrace("course logs sent \n");

        $data = array(
            'id' => $record->id,
            'courseid' => $record->courseid,
            'updatedat' => date('Y-m-d H:i:s'),
            'status' => 'done'
        );

        $DB->update_record('block_mad2api_course_logs', $data);

        mtrace("course log updated to done \n");

        return true;
    }
}
