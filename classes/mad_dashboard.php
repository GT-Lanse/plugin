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
    $tokenHex = bin2hex(random_bytes(16));
    $response = self::api_enable_call($courseId, $tokenHex);
    if(!property_exists($response, 'plugin-info')){
      return;
    }
    $row_check = $DB->get_record("mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => $courseId]);
    if (!$row_check) {
      $record = (object) array('user_id' => $USER->id,
                               'created_at' => date('Y-m-d H:i:s'),
                               'updated_at' => date('Y-m-d H:i:s'),
                               'last_log_date' => date('Y-m-d'),
                               'course_id' => $courseId,
                               'is_enabled' => 1,
                               'token' => $tokenHex,
                             );
      $database_response = $DB->insert_record('mad2api_dashboard_settings', $record, false);
      var_dump($database_response);
      self::upload_logs($courseId);
    } else {
      $update_grade_value = "
        UPDATE mdl_mad2api_dashboard_settings
        SET is_enabled=1
        WHERE user_id = $USER->id AND course_id = $courseId
      ";
      $database_response = $DB->execute($update_grade_value);
    }
    echo "enable";
    return $database_response;
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
    echo "api_enable_call";
    return json_decode($server_output);
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

  public static function upload_logs($courseId)
  {
    require_once('helpers/S3.php');
    global $DB, $COURSE, $CFG, $USER;
    $campus = get_config('mad2api', 'campus');
    $organization = get_config('mad2api', 'organization');
    $course_settings = $DB->get_record("mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => $courseId]);

    $s3 = new \S3("AKIARLANPF2DURY6V6P7", "eSFF5ojTveXsvMZTaIQT1pP3OoEEFfIi6PXYELvf", false);
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

  public static function get_dashboard_status()
  {
    global $USER, $DB, $COURSE;
    return $DB->get_record("mad2_user_prediction_setting", array('user_id'=>$USER->id, 'course_id'=>$COURSE->id), $fields='is_enabled');
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
