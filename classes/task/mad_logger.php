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
    global $DB;

    $records = $DB->get_records('mad2api_course_logs', array('status' => 'todo'));

    foreach ($records as $record) {
      $data = array(
        'id' => $record->id,
        'course_id' => $record->course_id,
        'updated_at' => date('Y-m-d H:i:s'),
        'status' => 'wip'
      );

      $DB->update_record('mad2api_course_logs', $data);

      \block_mad2api\mad_dashboard::api_send_students($record->course_id);
      \block_mad2api\mad_dashboard::api_send_logs($record->course_id);

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
