<?php
namespace block_mad2api;

defined('MOODLE_INTERNAL') || die();

# More enhancements will come later as the NN dev continues
class mad_dashboard{

  function __construct(){}

  public static function enable($gradeValue, $previousCourseId, $courseId)
  {
    global $DB, $USER;
    // If the user is going to enable prediction, it will set the grade value and update prediction settings
    $database_response = false;
    $row_check = $DB->get_record("mdl_mad2api_dashboard_settings", ['user_id' => $USER->id, 'course_id' => $courseId]);
    // It checks if there is any row with user_id, equals to the current-user id
    // otherwise will create a new row, since when the MAD prediction settings table
    // is created, the user ID comes without a value
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
