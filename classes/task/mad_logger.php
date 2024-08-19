<?php
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
    return 'MAD Logging';
  }

  /**
   * Execute the task.
   */
  public function execute() {
    global $DB, $CFG;

    $records = $DB->get_records_select(
      'mad2api_course_logs',
      "status = 'todo' OR status = 'wip'"
    );

    foreach ($records as $record) {
      $data = array(
        'id' => $record->id,
        'course_id' => $record->course_id,
        'updated_at' => date('Y-m-d H:i:s'),
        'status' => 'wip'
      );

      echo("Sending data from course #" . $record->course_id . "\n");

      $DB->update_record('mad2api_course_logs', $data);

      echo("course log updated to wip \n");

      echo("sending students \n");
      \block_mad2api\mad_dashboard::api_send_students($record->course_id);

      echo("sending logs \n");
      \block_mad2api\mad_dashboard::api_send_logs($record->course_id);

      echo("course logs sent \n");

      $data = array(
        'id' => $record->id,
        'course_id' => $record->course_id,
        'updated_at' => date('Y-m-d H:i:s'),
        'status' => 'done'
      );

      $DB->update_record('mad2api_course_logs', $data);
    }
  }
}
