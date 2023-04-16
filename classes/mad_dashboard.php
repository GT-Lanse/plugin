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
    global $DB, $USER;

    $params = self::validate_parameters(self::enable_parameters(),
      array(
        'courseId' => $courseId,
      )
    );

    $dashboardSetting = $DB->get_record(
      "mad2api_dashboard_settings",
      array('user_id' => $USER->id, 'course_id' => $courseId)
    );

    if (isset($dashboardSetting) && $dashboardSetting->is_enabled == 1) {
      $response = self::api_dashboard_auth_url($params['courseId']);

      if (!$response || !property_exists($response, 'url')) {
        return array(['enabled' => false, 'url' => '', 'error' => true]);
      }

      if (property_exists($response, 'url')) {
        return array(['enabled' => true, 'url' => $response->url, 'error' => false]);
      }
    }

    $databaseResponse = false;
    $response = self::api_enable_call($params['courseId']);

    if (!property_exists($response, 'url')) {
      return array(['enabled' => false, 'url' => '', 'error' => true]);
    }

    $recordDashboardSettings = array(
      'user_id' => $USER->id,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s'),
      'course_id' => intval($params['courseId']),
      'is_enabled' => 1,
      'token' => $USER->email,
    );

    if (isset($dashboardSetting->id)) {
      $recordDashboardSettings['id'] = $dashboardSetting->id;

      $databaseResponse = $DB->update_record('mad2api_dashboard_settings', $recordDashboardSettings, false);
    } else {
      $databaseResponse = $DB->insert_record('mad2api_dashboard_settings', $recordDashboardSettings, false);

      $recordCourseLogs = array(
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'course_id'  => intval($params['courseId']),
        'status'     => 'todo'
      );

      $DB->insert_record('mad2api_course_logs', $recordCourseLogs, false);
    }

    return array(['enabled' => $databaseResponse, 'url' => $response->url, 'error' => false ]);
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

    $databaseResponse = false;
    $dashboardSetting = $DB->get_record(
      "mad2api_dashboard_settings",
      ['user_id' => $USER->id, 'course_id' => $courseId]
    );

    if (isset($dashboardSetting->id)) {
      $data['id'] = $dashboardSetting->id;
      $databaseResponse = $DB->update_record('mad2api_dashboard_settings', $data);
    }

    return array(['disabled' => $databaseResponse]);
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
    global $DB;

    return $DB->get_records('role_assignments', array('contextid' => $contextid, 'userid' => $userid));
  }

  public static function api_enable_call($courseId) {
    global $USER, $DB;

    $course = $DB->get_record('course', ['id' => $courseId]);

    $enable = array(
      'course' => array(
        'startDate' => '2023-03-26 11:23:05.999760',
        'endDate' => '2023-03-26 11:23:05.999773',
        'name' => $course->fullname
      ),
      'teacher' => array(
        'teacherId' => $USER->id,
        'firstName' => $USER->firstname,
        'lastName' => $USER->lastname,
        'email' => $USER->email
      )
    );

    $auth = array(
      'teacherId' => $USER->id,
      'moodleId' => $courseId
    );

    self::do_post_request("api/v2/courses/{$courseId}/enable", $enable);

    $resp = self::do_post_request("api/v2/authorize", $auth);

    return $resp->data;
  }

  public static function api_send_students($courseId) {
    global $DB;

    $count = self::get_course_students_count($courseId);
    $perPage = 20;
    $endPage = $count / $perPage;

    for ($currentPage = 1; $currentPage <= $endPage; $currentPage++) {
      $offset = ($currentPage - 1) * $perPage;

      $data = array(
        'students' => self::get_course_students($courseId, $perPage, $offset)
      );

      self::do_post_request("api/v2/courses/${courseId}/students/batch", $data);
    }
  }

  public static function api_send_logs($courseId) {
    global $DB;

    $countSql = "
      SELECT  COUNT(*)
      FROM mdl_logstore_standard_log m
      JOIN mdl_role_assignments B
      JOIN mdl_course mc on mc.id = m.courseid
      JOIN mdl_user mu on mu.id = m.userid
      WHERE B.roleid = 5 AND m.courseid = {$courseId} AND B.userid = m.userid
    ";
    $count = $DB->count_records_sql($countSql);
    $perPage = 20;
    $endPage = $count / $perPage;

    for ($currentPage = 1; $currentPage <= $endPage; $currentPage++) {
      $offset = ($currentPage - 1) * $perPage;
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
        WHERE B.roleid = 5 AND m.courseid = {$courseId} AND B.userid = m.userid
        GROUP BY m.id
        LIMIT {$perPage} OFFSET {$offset}
      ";

      $data = array(
        'logs' => $DB->get_records_sql($logs_query)
      );

      self::do_post_request("api/v2/courses/{$courseId}/logs/batch", $data);
    }
  }

  public static function api_dashboard_auth_url($courseId){
    global $USER, $DB;

    $auth = array(
      'teacherId' => $USER->id,
      'moodleId' => $courseId
    );

    $resp = self::do_post_request("api/v2/authorize", $auth);

    return $resp->data;
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

  public static function get_course_students($courseId, $perPage, $offset) {
    global $DB;

    $students = $DB->get_records_sql("
      SELECT u.id AS student_id, u.email,
      u.firstname AS first_name, u.lastname AS last_name,
      (CASE WHEN lastaccess = '0' THEN 'false' ELSE 'true' END) AS logged_in,
      AVG(g.rawgrade) AS current_grade
      FROM {course} c
      JOIN {context} ct ON c.id = ct.instanceid
      JOIN {role_assignments} ra ON ra.contextid = ct.id
      JOIN {user} u ON u.id = ra.userid
      JOIN {role} r ON r.id = ra.roleid
      LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid IN (
        SELECT gi.id
        FROM {grade_items} gi
        WHERE gi.courseid = {$courseId}
      )
      WHERE c.id = {$courseId} AND r.id = 5
      GROUP BY u.id
      LIMIT {$perPage} OFFSET {$offset}
    ");

    return self::camelizeArray($students);
  }

  public static function camelizeObject($obj) {
    $new_obj = array();

    foreach($obj as $key => $value) {
      $new_obj[self::convertToCamel($key, '_')] = $value;
    }

    return $new_obj;
  }

  public static function camelizeArray($array) {
    $formattedArray = [];

    foreach ($array as $item) {
      array_push($formattedArray, self::camelizeObject($item));
    }

    return $formattedArray;
  }

  private static function convertToCamel($str, $delim) {
    $exploded_str = explode($delim, $str);
    $exploded_str_camel = array_map('ucwords', $exploded_str);

    return lcfirst(implode($exploded_str_camel, ''));
  }

  private static function do_post_request($url, $body)
  {
    $apiKey = get_config('mad2api', 'api_key');
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));  //Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
      'accept: application/json',
      'Content-Type: application/json',
      "API-KEY: {$apiKey}"
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    curl_close($ch);

    return json_decode($response);
  }

  private static function get_url_for($path)
  {
    $apiUrl = get_config('mad2api', 'api_url');

    return "{$apiUrl}/{$path}";
  }
}
