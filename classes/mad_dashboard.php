<?php
namespace block_mad2api;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use external_multiple_structure;

# More enhancements will come later as the NN dev continues
class mad_dashboard extends external_api {
  function __construct(){}

  public static function enable_parameters()
  {
    return new external_function_parameters([
      'courseId' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
    ]);
  }

  public static function enable_returns()
  {
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

    if ($response == null || !property_exists($response, 'url')) {
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
    }

    $recordCourseLogs = array(
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s'),
      'course_id'  => intval($params['courseId']),
      'status'     => 'todo'
    );

    $courseLog = $DB->get_record(
      "mad2api_course_logs", array('course_id' => $courseId)
    );

    if (!isset($courseLog->id)) {
      $DB->insert_record('mad2api_course_logs', $recordCourseLogs, false);
    }

    return array(['enabled' => $databaseResponse, 'url' => $response->url, 'error' => false ]);
  }

  public static function disable_parameters()
  {
    return new external_function_parameters([
      'courseId' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
    ]);
  }

  public static function disable_returns()
  {
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

  public static function check_data_on_api($courseId)
  {
    global $DB;

    $lastCourseLog = array_slice($DB->get_records(
      "mad2api_course_logs", array('course_id' => $courseId, 'status' => 'done')
    ), -1);

    $courseLog = !empty($lastCourseLog) ? $lastCourseLog[0] : null;

    if (!$courseLog) {
      return;
    }

    $response = self::api_check_course_data($courseId);

    if ($response != null && isset($response->resend_data) && $response->resend_data) {
      $updatedAttributes = array(
        'id' => $courseLog->id,
        'status' => 'todo',
        'students_sent' => 1,
        'last_log_page' => 1,
        'updated_at' => date('Y-m-d H:i:s')
      );

      $databaseResponse = $DB->update_record(
        'mad2api_course_logs', $updatedAttributes, false
      );
    }
  }

  public static function is_current_user_course_teacher($contextid)
  {
    global $USER;

    $isPermitted = false;
    $permittedRoles = explode(',', get_config('mad2api', 'roles'));

    foreach (self::get_user_roles($USER->id, $contextid) as $user_role) {
      if (in_array($user_role->roleid, $permittedRoles)) {
        $isPermitted = true;
      }
    }

    return $isPermitted;
  }

  public static function get_user_roles($userid, $contextid)
  {
    global $DB;

    return $DB->get_records('role_assignments', array('contextid' => $contextid, 'userid' => $userid));
  }

  private static function send_settings_to_api()
  {
    global $DB;

    $apiSetting = $DB->get_records("mad2api_api_settings")[0];

    if (!$apiSetting || ($apiSetting->sent_at < new DateTime("today"))) {
      return;
    }

    $updatedAttributes = array(
      'id' => $apiSetting->id,
      'sent_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    );

    $DB->update_record(
      'mad2api_api_settings', $updatedAttributes, false
    );

    $settings = array(
      'pluginVersion' => \core_plugin_manager::instance()->get_plugin_info('block_mad2api')->release,
      'moodleVersion' => $CFG->release,
    );

    self::do_put_request("api/v2/settings/organizations/", $settings);
  }

  public static function api_installation_call()
  {
    global $CFG;

    $settings = array(
      'pluginVersion' => \core_plugin_manager::instance()->get_plugin_info('block_mad2api')->release,
      'moodleVersion' => $CFG->release,
      'installationDate' => date('Y-m-d H:i:s')
    );

    self::do_put_request("api/v2/settings/organizations/", $settings);
  }

  public static function api_enable_call($courseId)
  {
    global $USER, $DB;

    $course = $DB->get_record('course', ['id' => $courseId]);

    $enable = array(
      'course' => array(
        'startDate' => $course->startdate,
        'endDate' => $course->enddate,
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
    self::send_settings_to_api();

    $resp = self::do_post_request("api/v2/authorize", $auth);

    return $resp->data;
  }

  public static function api_check_course_data($courseId)
  {
    return self::do_get_request("api/v2/plugin/courses/{$courseId}/resend_data");
  }

  public static function api_send_students($courseId)
  {
    global $DB;

    $courseLog = $DB->get_record(
      "mad2api_course_logs", array('course_id' => $courseId, 'students_sent' => 1)
    );

    if (!!$courseLog) {
      return;
    }

    $count = self::get_course_students_count($courseId);
    $perPage = 20;
    $endPage = ceil($count / $perPage);

    for ($currentPage = 1; $currentPage <= $endPage; $currentPage++) {
      $offset = ($currentPage - 1) * $perPage;

      $data = array(
        'students' => self::get_course_students($courseId, $perPage, $offset)
      );

      self::do_post_request("api/v2/courses/${courseId}/students/batch", $data, $courseId);
    }

    $courseLog = $DB->get_record(
      "mad2api_course_logs", array('course_id' => $courseId)
    );

    $updatedAttributes = array(
      'id' => $courseLog->id,
      'students_sent' => 1,
      'updated_at' => date('Y-m-d H:i:s')
    );

    $DB->update_record(
      'mad2api_course_logs', $updatedAttributes, false
    );
  }

  public static function api_send_logs($courseId)
  {
    global $DB, $CFG;

    $courseLog = $DB->get_record(
      "mad2api_course_logs", array('course_id' => $courseId, 'status' => 'done')
    );

    if (!!$courseLog) {
      return;
    }

    $courseLog = $DB->get_record(
      "mad2api_course_logs", array('course_id' => $courseId)
    );

    $countSql = "
      SELECT COUNT(DISTINCT m.id)
      FROM {$CFG->prefix}logstore_standard_log m
      WHERE m.courseid = {$courseId}
    ";
    $count = $DB->count_records_sql($countSql);
    $perPage = 100;
    $endPage = ceil($count / $perPage);

    for ($currentPage = $courseLog->last_log_page; $currentPage <= $endPage; $currentPage++) {
      $updatedAttributes = array(
        'id' => $courseLog->id,
        'last_log_page' => $currentPage,
        'updated_at' => date('Y-m-d H:i:s')
      );

      $DB->update_record(
        'mad2api_course_logs', $updatedAttributes, false
      );

      $offset = ($currentPage - 1) * $perPage;
      $logs_query = "
        SELECT m.*
        FROM {$CFG->prefix}logstore_standard_log m
        INNER JOIN {$CFG->prefix}role_assignments ra ON ra.userid = m.userid
        INNER JOIN {$CFG->prefix}course mc ON mc.id = m.courseid
        WHERE m.courseid = {$courseId}
        GROUP BY m.id
        LIMIT {$perPage} OFFSET {$offset}
      ";
      $data = array(
        'logs' => $DB->get_records_sql($logs_query)
      );

      self::do_post_request("api/v2/courses/{$courseId}/logs/batch", $data, $courseId);
    }
  }

  public static function api_dashboard_auth_url($courseId)
  {
    global $USER, $DB;

    $auth = array(
      'teacherId' => $USER->id,
      'moodleId' => $courseId
    );

    $resp = self::do_post_request("api/v2/authorize", $auth, $courseId);

    return isset($resp->data) ? $resp->data : array();
  }

  public static function get_course_students_count($courseId)
  {
    global $DB;

    $studentRole = get_config('mad2api', 'studentRole');

    return $DB->count_records_sql("
      SELECT  COUNT(*)
      FROM {course} c
      JOIN {context} ct ON c.id = ct.instanceid
      JOIN {role_assignments} ra ON ra.contextid = ct.id
      JOIN {user} u ON u.id = ra.userid
      JOIN {role} r ON r.id = ra.roleid
      where c.id = {$courseId} AND r.id = {$studentRole}
    ");
  }

  public static function get_course_students($courseId, $perPage, $offset)
  {
    global $DB;

    $studentRole = get_config('mad2api', 'studentRole');

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
      WHERE c.id = {$courseId} AND r.id = {$studentRole}
      GROUP BY u.id
      LIMIT {$perPage} OFFSET {$offset}
    ");

    return self::camelizeArray($students);
  }

  public static function get_course_student($courseId, $studentId)
  {
    global $DB;

    $studentRole = get_config('mad2api', 'studentRole');

    $student = $DB->get_record_sql("
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
      WHERE c.id = {$courseId} AND r.id = {$studentRole} AND u.id = {$studentId}
    ");

    return self::camelizeObject($student);
  }

  public static function camelizeObject($obj)
  {
    $new_obj = array();

    if (gettype($obj) == 'boolean') {
      return $new_obj;
    }

    foreach($obj as $key => $value) {
      $new_obj[self::convertToCamel($key, '_')] = $value;
    }

    return $new_obj;
  }

  public static function camelizeArray($array)
  {
    $formattedArray = [];

    foreach ($array as $item) {
      array_push($formattedArray, self::camelizeObject($item));
    }

    return $formattedArray;
  }

  private static function disable_course_if_not_found($ch, $courseId)
  {
    global $DB;

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (in_array($http_status, [400, 404])) {
      $resources = $DB->get_records(
        "mad2api_dashboard_settings",
        array('course_id' => $courseId, 'is_enabled' => true)
      );

      foreach ($resources as $resource) {
        $data = array(
          'id' => $resource->id,
          'is_enabled' => false
        );

        $databaseResponse = $DB->update_record(
          'mad2api_dashboard_settings', $data, false
        );
      }
    }
  }

  private static function convertToCamel($str, $delim)
  {
    $exploded_str = explode($delim, $str);
    $exploded_str_camel = array_map('ucwords', $exploded_str);

    return lcfirst(implode('', $exploded_str_camel));
  }

  public static function is_course_enabled($courseId)
  {
    global $DB;

    return !!$DB->get_record(
      "mad2api_dashboard_settings",
      array('course_id' => $courseId, 'is_enabled' => true)
    );
  }

  public static function do_post_request($url, $body, $courseId = null)
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

    if (isset($courseId)) {
      self::disable_course_if_not_found($ch, $courseId);
    }

    curl_close($ch);

    return json_decode($response);
  }

  private static function do_put_request($url, $body)
  {
    $apiKey = get_config('mad2api', 'api_key');
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
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

  private static function do_get_request($url)
  {
    $apiKey = get_config('mad2api', 'api_key');
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
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
    // $apiUrl = "http://host.docker.internal:8080";
    $apiUrl = "https://api.lanse.com.br";

    return "{$apiUrl}/{$path}";
  }
}
