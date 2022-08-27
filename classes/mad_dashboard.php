<?php
namespace block_mad2api;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once('task/mad_send_logs.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_multiple_structure;

# More enhancements will come later as the NN dev continues
class mad_dashboard extends external_api {
  function __construct(){}

  public static function enable_parameters() {
    return new external_function_parameters([
      'courseId' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
    ]);
  }

  public static function enable_returns() {
    return new external_multiple_structure(
      new external_single_structure(
        array(
          'enabled' => new external_value(PARAM_BOOL, VALUE_DEFAULT, true),
          'url' => new external_value(PARAM_TEXT, VALUE_DEFAULT, ""),
          'error' => new external_value(PARAM_BOOL, VALUE_DEFAULT, false)
        )
      )
    );
  }

  public static function enable($courseId)
  {
    global $DB, $USER, $COURSE;

    $params = self::validate_parameters(self::enable_parameters(),
       array(
         'courseId' => $courseId,
       )
     );

    $dashboard_setting = $DB->get_record(
      "mad2api_dashboard_settings",
      array('user_id' => $USER->id, 'course_id' => $courseId)
    );


    if (isset($dashboard_setting) && $dashboard_setting->is_enabled == 1) {
      $response = self::api_dashboard_auth_url($params['courseId']);

      if (!property_exists($response, 'url')) {
        return array(['enabled' => false, 'url' => '', 'error' => true]);
      }

      if (property_exists($response, 'url')) {
        return array(['enabled' => true, 'url' => $response->url, 'error' => false]);
      }
    }

    $database_response = false;
    $response = self::api_enable_call($params['courseId']);

    if (!property_exists($response, 'url')) {
      return array(['enabled' => false, 'url' => '', 'error' => true]);
    }

    $task = new \block_mad2api\task\mad_send_logs();
    $task->set_blocking(false);
    $task -> set_next_run_time(time() + 10);
    $task->set_custom_data(array(
        'course_id' => $params['courseId']
    ));

    \core\task\manager::queue_adhoc_task($task, true);

    $record = array(
      'user_id' => $USER->id,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s'),
      'last_log_date' => date('Y-m-d'),
      'course_id' => intval($params['courseId']),
      'is_enabled' => 1,
      'token' => $USER->email,
    );

    if (isset($dashboard_setting->id)) {
      $record['id'] = $dashboard_setting->id;
      $database_response = $DB->update_record('mad2api_dashboard_settings', $record, false);
    } else {
      $database_response = $DB->insert_record('mad2api_dashboard_settings', $record, false);
    }

    return array(['enabled' => $database_response, 'url' => $response->url, 'error' => false ]);
  }

  public static function disable_parameters() {
    return new external_function_parameters([
      'courseId' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
    ]);
  }

  public static function disable_returns() {
    return new external_multiple_structure(
      new external_single_structure(
        array(
          'disabled' => new external_value(PARAM_BOOL, VALUE_REQUIRED)
        )
      )
    );
  }

  public static function disable($courseId)
  {
   global $USER, $DB;

    $params = self::validate_parameters(self::enable_parameters(),
      array(
        'courseId' => $courseId,
      )
    );

    $data = array(
      'user_id' => $USER->id,
      'course_id' => $courseId,
      'updated_at' => date('Y-m-d H:i:s'),
      'is_enabled' => 0
    );

    $database_response = false;
    $dashboard_setting = $DB->get_record(
      "mad2api_dashboard_settings",
      ['user_id' => $USER->id, 'course_id' => $courseId]
    );

    if (isset($dashboard_setting->id)) {
      $data['id'] = $dashboard_setting->id;
      $database_response = $DB->update_record('mad2api_dashboard_settings', $data);
    }

    return array(['disabled' => $database_response]);
  }

  public static function is_current_user_course_teacher($contextid) {
    global $USER;

    $is_teacher = false;

    foreach (self::get_user_roles($USER->id, $contextid) as $user_role) {
      if ($user_role->roleid == 4 || $user_role->roleid == 3) {
        $is_teacher = true;
      }
    }

    return $is_teacher;
  }

  public static function get_user_roles($userid, $contextid) {
    global $COURSE, $DB;

    return $DB->get_records('role_assignments', array('contextid' => $contextid, 'userid' => $userid));
  }

  public static function api_enable_call($courseId){
    global $COURSE, $USER, $DB;

    $access_key = get_config('mad2api', 'access_key');
    $aws_secret_key = get_config('mad2api', 'aws_secret_key');
    $api_key = get_config('mad2api', 'api_key');
    $organization = get_config('mad2api', 'organization');

    $data = array(
      'class_code' => $courseId,
      'organization' => $organization,
      'teacher' => array(
        'id' => $USER->id,
        'firstname' => $USER->firstname,
        'lastname' => $USER->lastname,
        'email' => $USER->email
      ),
      'professor' => array(
        'id' => $USER->id,
        'name' => $USER->firstname,
        'email' => $USER->email,
        'code_id' => $USER->email
      ),
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,"api.lanse.prd.apps.kloud.rnp.br/api/plugin/enable");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  //Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
      'accept: application/json',
      'Content-Type: application/json',
      "API-KEY: {$api_key}"
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $server_output = curl_exec($ch);

    curl_close($ch);

    return json_decode($server_output);
  }

  public static function api_send_students($course_id) {
    global $DB;

    $api_key = get_config('mad2api', 'api_key');
    $count = self::get_course_students_count($course_id);
    $per_page = 20;
    $end_page = $count / $per_page;

    for ($current_page = 1; $current_page <= $end_page; $current_page++) {
      $offset = ($current_page - 1) * $per_page;

      $data = array(
        'course_id' => $course_id,
        'students' => self::get_course_students($course_id, $per_page, $offset)
      );
      $headers = array(
        'accept: application/json',
        'Content-Type: application/json',
        "API-KEY: {$api_key}"
      );

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL,"http://localhost:8080/students");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  //Post Fields
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $server_output = curl_exec($ch);

      curl_close($ch);
    }
  }

  public static function api_send_logs($course_id) {
    global $DB;

    $api_key = get_config('mad2api', 'api_key');

    $count_sql = "
      SELECT  COUNT(*)
      FROM mdl_logstore_standard_log m
      JOIN mdl_role_assignments B
      JOIN mdl_course mc on mc.id = m.courseid
      JOIN mdl_user mu on mu.id = m.userid
      WHERE B.roleid = 5 AND m.courseid = 2 AND B.userid = m.userid
    ";
    $count = $DB->count_records_sql($count_sql);
    $per_page = 20;
    $end_page = $count / $per_page;

    for ($current_page = 1; $current_page <= $end_page; $current_page++) {
      $offset = ($current_page - 1) * $per_page;
      $logs_query = "
        SELECT  m.id AS id,
                FROM_UNIXTIME(m.timecreated) AS hour,
                CONCAT(mu.firstname, ' ',mu.lastname) AS name,
                m.eventname AS context,
                m.component AS component
        FROM mdl_logstore_standard_log m
        JOIN mdl_role_assignments B
        JOIN mdl_course mc on mc.id = m.courseid
        JOIN mdl_user mu on mu.id = m.userid
        WHERE B.roleid = 5 AND m.courseid = {$course_id} AND B.userid = m.userid
        GROUP BY m.id
        LIMIT {$per_page} OFFSET {$offset}
      ";
      $data = array(
        'course_id' => $course_id,
        'logs' => $DB->get_records_sql($logs_query)
      );
      $headers = array(
        'accept: application/json',
        'Content-Type: application/json',
        "API-KEY: {$api_key}"
      );

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL,"https://api.lanse.prd.apps.kloud.rnp.br/api/plugin/courses/{$course_id}/logs");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  //Post Fields
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $server_output = curl_exec($ch);

      curl_close($ch);
    }
  }

  public static function api_dashboard_auth_url($courseId){
    global $COURSE, $USER, $DB;

    $api_key = get_config('mad2api', 'api_key');

    $data = array(
      'class_code' => $courseId,
      "code_id" => $USER->email,
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://api.lanse.prd.apps.kloud.rnp.br/api/plugin/enabled");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  //Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
      'accept: application/json',
      'Content-Type: application/json',
      "API-KEY: {$api_key}",

    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $server_output = curl_exec($ch);
    curl_close($ch);

    return json_decode($server_output);
  }

  public static function get_course_students_count($courseId) {
    global $DB;

    return $DB->count_records_sql("
      SELECT  COUNT(*)
      FROM {course} c
      JOIN {context} ct ON c.id = ct.instanceid
      JOIN {role_assignments} ra ON ra.contextid = ct.id
      JOIN {user} u ON u.id = ra.userid
      JOIN {role} r ON r.id = ra.roleid
      where c.id = {$courseId} AND r.id = 5
    ");
  }

  public static function get_course_students($course_id, $per_page, $offset) {
    global $DB;

    $students = $DB->get_records_sql("
      SELECT u.id, u.firstname, u.lastname, u.email
      FROM {course} c
      JOIN {context} ct ON c.id = ct.instanceid
      JOIN {role_assignments} ra ON ra.contextid = ct.id
      JOIN {user} u ON u.id = ra.userid
      JOIN {role} r ON r.id = ra.roleid
      WHERE c.id = {$course_id} AND r.id = 5
      GROUP BY u.id
      LIMIT {$per_page} OFFSET {$offset}
    ");

    return $students;
  }
}
