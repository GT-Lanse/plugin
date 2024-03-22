<?php
namespace block_mad2api\task;

defined('MOODLE_INTERNAL') || die();

class mad_check_resend_data extends \core\task\scheduled_task {
  public function get_name() {
    return 'MAD2 check resend data';
  }

  public function execute() {
    global $DB;

    $records = $DB->get_records(
      'mad2api_course_logs', array('status' => 'done')
    );

    foreach ($records as $record) {
      \block_mad2api\mad_dashboard::check_data_on_api($record->course_id);
    }
  }
}
