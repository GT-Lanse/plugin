<?php
namespace block_mad2api;

defined('MOODLE_INTERNAL') || die();

# More enhancements will come later as the NN dev continues
class mad_dashboard{

  function __construct(){}

  public static function enable()
  {
    global $DB, $USER, $COURSE;
    $database_response = false;
    $row_check = $DB->get_record("mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => $COURSE->id]);
    if (!$row_check) {
      $record = (object) array('user_id' => $USER->id,
                               'created_at' => date(),
                               'updated_at' => date(),
                               'last_log_date' => date(),
                               'course_id' => $courseId,
                               'is_enabled' => 1,
                               'grade_value_target' => $gradeValue);
      $database_response = $DB->insert_record('mdl_mad2api_dashboard_settings', $record, false);
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
    var_dump( $DB->get_record("mdl_mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => $COURSE->id]));
    return;
    $campus = get_config('mad2api', 'campus');
    $organization = get_config('mad2api', 'organization');
    $data = array('class_code' => $COURSE->fullname,
                  'campus' => $campus,
                  'organization' => $organization,
                  'professor' => array(
                    "name" => "$USER->firstname $USER->lastname",
                    "email" => $USER->email,
                    "code_id" => "tststadadststs",
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
