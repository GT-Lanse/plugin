<?php
namespace block_mad2api;
//use helpers\S3;
defined('MOODLE_INTERNAL') || die();

// require(__DIR__.'/../../config.php');

require_once("$CFG->libdir/externallib.php");
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
          'enabled' => new external_value(PARAM_BOOL, VALUE_REQUIRED)
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
      return array(['enabled' => true]);
    }

    $database_response = false;
    $tokenHex = bin2hex(random_bytes(16));
    $response = self::api_enable_call($params['courseId'], $tokenHex);

    if (!property_exists($response, 'plugin-info')) {
      return;
    }

    $record = array(
      'user_id' => $USER->id,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s'),
      'last_log_date' => date('Y-m-d'),
      'course_id' => intval($params['courseId']),
      'is_enabled' => 1,
      'token' => $tokenHex
    );

    if (isset($dashboard_setting->id)) {
      $record['id'] = $dashboard_setting->id;
      $database_response = $DB->update_record('mad2api_dashboard_settings', $record, false);
    } else {
      $database_response = $DB->insert_record('mad2api_dashboard_settings', $record, false);
    }

    self::upload_logs($params['courseId']);

    return array(['enabled' => $database_response]);
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

  public static function api_enable_call($courseId, $tokenHex){
    global $COURSE, $USER, $DB;
    $campus = get_config('mad2api', 'campus');
    $organization = get_config('mad2api', 'organization');
    $data = array(
      'class_code' => $courseId,
      'campus' => $campus,
      'organization' => $organization,
      'professor' => array(
        "name" => "$USER->firstname $USER->lastname",
        "email" => $USER->email,
        "code_id" => $tokenHex,
      ),
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/api/plugin/enable");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));  //Post Fields
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = [
      'accept: application/json',
      'Content-Type: application/json',
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $server_output = curl_exec($ch);
    curl_close($ch);

    return json_decode($server_output);
  }

  public static function get_last_log_date()
  {
    global $DB, $COURSE;
    return $DB->get_records_sql("
      SELECT last_log_date
      FROM mdl_mad2api_dashboard_settings
      WHERE course_id = $COURSE->id"
    );
  }

  public static function get_course_start_end_date()
  {
    global $DB, $COURSE;
    return $DB->get_records_sql("
      SELECT FROM_UNIXTIME(startdate, '%d/%m/%Y') AS startdate, FROM_UNIXTIME(enddate, '%d/%m/%Y') AS enddate
      FROM mdl_course
      WHERE id = $COURSE->id"
    );
  }

  public static function upload_logs($courseId)
  {
    require_once('helpers/S3.php');
    global $DB, $COURSE, $CFG, $USER;
    $campus = get_config('mad2api', 'campus');
    $organization = get_config('mad2api', 'organization');
    $course_settings = $DB->get_record("mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => $courseId]);

 $s3 = new \S3("KEY", "TOKEN", false);
    // echo "S3::listBuckets(): ".print_r($s3->listBuckets(), 1)."\n";
    // return;
    $logs_query = '
    SELECT  m.id AS Id,
            FROM_UNIXTIME(m.timecreated) AS "Hora",
            CONCAT(mu.firstname, " ",mu.lastname) AS "Nome completo",
            m.eventname AS "Contexto do Evento"
    FROM mdl_logstore_standard_log m
    JOIN mdl_role_assignments B
    JOIN mdl_course mc on mc.id = m.courseid
    JOIN mdl_user mu on mu.id = m.userid
    WHERE B.roleid = 5 AND m.courseid = 8 AND B.userid = m.userid
    GROUP BY m.id
    ';
    $logs = $DB->get_records_sql($logs_query);

    $logs = array_values($logs);

    if(!function_exists('str_putcsv'))
    {
        function str_putcsv($inputs, $delimiter = ',', $enclosure = '"')
        {
            $header = array(
              0 => 'Id',
              1 => 'Hora',
              2 => 'Nome Completo',
              3 => 'Contexto do Evento',
            );
            $fp = fopen('temp.csv', 'w');
            fputcsv($fp, $header);
            foreach ($inputs as $input) {
              fputcsv($fp, get_object_vars($input));
            }
            fclose($fp);
        }
     }
    str_putcsv($logs);
    $s3->putObject(file_get_contents('./temp.csv'), 'futurogfp-documents', "unprocessed/$organization/$campus/$course_settings->token/$courseId.csv", \S3::ACL_PRIVATE, array(), array('Content-Type' => 'text/csv'));
  }

  public static function get_dashboard_status($courseId)
  {
    global $USER, $DB;

    return $DB->get_record(
      "mad2api_dashboard_settings",
      array(
        'user_id' => $USER->id,
        'course_id' => $courseId,
        'is_enabled' => 1
      )
    );
  }

  public static function scheduled_log(){
    global $DB, $COURSE;
    $query = "
      SELECT course_id
      FROM `mdl_mad2api_dashboard_settings`
      WHERE is_enabled = 1;
    ";
    $active_courses = $DB->get_records_sql($query);
    foreach($active_courses as $active_course){
      self::upload_logs($active_course->course_id);
    }
  }
}
