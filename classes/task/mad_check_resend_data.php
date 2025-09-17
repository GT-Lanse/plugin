<?php
namespace block_mad2api\task;

defined('MOODLE_INTERNAL') || die();

class mad_check_resend_data extends \core\task\scheduled_task {
  public function get_name() {
    return 'LANSE Check Data To Be Resend';
  }

  public function execute() {
    global $DB;

    $records = $DB->get_records('mad2api_course_logs');

    mtrace("Checking resend data for " . count($records) . " courses \n");

    foreach ($records as $record) {
      mtrace("Checking resend for course #" . $record->course_id . "\n");

      \block_mad2api\mad_dashboard::check_data_on_api($record->course_id);
    }

    mtrace("Checking pending activities \n");

    \block_mad2api\mad_dashboard::send_pending_activities();
  }
}
