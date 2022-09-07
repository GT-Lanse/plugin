<?php
namespace block_mad2api\task;
defined('MOODLE_INTERNAL') || die();

class mad_send_logs extends \core\task\adhoc_task {
  public function execute() {
    $data = $this->get_custom_data();
    $course_id = $data->course_id;

    \block_mad2api\mad_dashboard::api_send_logs($course_id);
    \block_mad2api\mad_dashboard::api_send_students($course_id);
  }
}