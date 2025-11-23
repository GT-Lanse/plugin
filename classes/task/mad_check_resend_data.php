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
    return 'LANSE Check Data To Be Resend';
  }

  public function execute() {
    global $DB;

    $records = $DB->get_records('block_mad2api_course_logs');

    mtrace("Checking resend data for " . count($records) . " courses \n");

    foreach ($records as $record) {
      mtrace("Checking resend for course #" . $record->courseid . "\n");

      \block_mad2api\mad_dashboard::check_data_on_api($record->courseid);
    }

    mtrace("Checking pending activities \n");

    \block_mad2api\mad_dashboard::send_pending_activities();
  }
}
