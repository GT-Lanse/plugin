<?php
namespace block_mad2api\task;
defined('MOODLE_INTERNAL') || die();

class mad_send_logs extends \core\task\adhoc_task {
  public function execute() {
    $data = $this->get_custom_data();
    $courseId = $data->course_id;

    $courseLog = $DB->get_record(
      "mad2api_course_logs", array('course_id' => $courseId, 'status' => 'done')
    );

    if (!!$courseLog) {
      return
    }

    \block_mad2api\mad_dashboard::api_send_students($courseId);
    \block_mad2api\mad_dashboard::api_send_logs($courseId);
  }
}
