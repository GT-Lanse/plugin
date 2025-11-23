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
 * An example of a scheduled task.
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
   */
  public function execute() {
    global $DB, $CFG;

    $records = $DB->get_records_select(
      'block_mad2api_course_logs',
      "status = 'todo' OR status = 'wip'"
    );

    foreach ($records as $record) {
      $data = array(
        'id' => $record->id,
        'courseid' => $record->courseid,
        'updated_at' => date('Y-m-d H:i:s'),
        'status' => 'wip'
      );

      mtrace("Sending data from course #" . $record->courseid . "\n");

      $DB->update_record('block_mad2api_course_logs', $data);

      mtrace("course log updated to wip \n");

      mtrace("sending students \n");
      \block_mad2api\mad_dashboard::api_send_students($record->courseid);

      mtrace("sending logs \n");
      \block_mad2api\mad_dashboard::api_send_logs($record->courseid);

      mtrace("course logs sent \n");

      $data = array(
        'id' => $record->id,
        'course_id' => $record->courseid,
        'updated_at' => date('Y-m-d H:i:s'),
        'status' => 'done'
      );

      $DB->update_record('block_mad2api_course_logs', $data);

      mtrace("course log updated to done \n");
    }
  }
}
