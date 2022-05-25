<?php
namespace block_mad2api;
//use helpers\S3;
defined('MOODLE_INTERNAL') || die();

# More enhancements will come later as the NN dev continues
class mad_dashboard{
  function __construct(){}

  public static function enable($courseId)
  {
    global $DB, $USER, $COURSE;
    $database_response = false;
    $row_check = $DB->get_record("mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => $courseId]);
    if (!$row_check) {
      $record = (object) array('user_id' => $USER->id,
                               'created_at' => date('Y-m-d H:i:s'),
                               'updated_at' => date('Y-m-d H:i:s'),
                               'last_log_date' => date('Y-m-d'),
                               'course_id' => $courseId,
                               'is_enabled' => 1,
                               'token' => bin2hex(random_bytes(16)));
      $database_response = $DB->insert_record('mad2api_dashboard_settings', $record, false);
      //api_enable_call()
      //upload logs here
    } else {
      $update_grade_value = "
        UPDATE mdl_mad2api_dashboard_settings
        SET is_enabled=1
        WHERE user_id = $USER->id AND course_id = $courseId
      ";
      $database_response = $DB->execute($update_grade_value);
    }
    return $database_response;
  }

  public static function api_enable_call(){
    global $COURSE, $USER, $DB;
    $course_settings = $DB->get_record("mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => 4]);
    $campus = get_config('mad2api', 'campus');
    $organization = get_config('mad2api', 'organization');
    $data = array(
      'class_code' => $COURSE->fullname,
      'campus' => $campus,
      'organization' => $organization,
      'professor' => array(
        "name" => "$USER->firstname $USER->lastname",
        "email" => $USER->email,
        "code_id" => $course_settings->token,
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

    echo $server_output;
  }

  public static function disable($courseId)
  {
    global $USER, $DB;
    $update_query = "
      UPDATE mdl_mad2api_dashboard_settings
      SET is_enabled=0
      WHERE user_id=$USER->id AND course_id=$courseId
    ";
    return $DB->execute($update_query);
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

  public static function upload_logs($course_id)
  {
    require_once('helpers/S3.php');
    global $DB, $COURSE, $CFG;

    $s3 = new \S3(" KEY ", " SECRET KEY", false);
    // echo "S3::listBuckets(): ".print_r($s3->listBuckets(), 1)."\n";
    // return;
    $logs_query = "
      SELECT m.*
      FROM mdl_logstore_standard_log m
      INNER JOIN mdl_role_assignments B
      WHERE B.roleid = 5 AND m.courseid = $course_id AND B.userid = m.userid
      GROUP BY m.id
    ";
    $logs = $DB->get_records_sql($logs_query);

    $s3->putObject(json_encode($logs), 'futurogfp-documents', 'development/teste.json', \S3::ACL_PRIVATE, array(), array('Content-Type' => 'application/json'));
  }

  public static function get_dashboard_status()
  {
    global $USER, $DB, $COURSE;
    return $DB->get_record("mad2_user_prediction_setting", array('user_id'=>$USER->id, 'course_id'=>$COURSE->id), $fields='is_enabled');
  }

  public static function scheduled_log(){
    global $DB, $USER, $COURSE;
    $query = "
      SELECT course_id
      FROM `mdl_mad2api_dashboard_settings`
      WHERE is_enabled = 1;
    ";
    $active_courses = $DB->get_records_sql($query);
    foreach($active_courses as $active_course){
      //send data
    }
  }

}
