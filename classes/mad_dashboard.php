<?php
namespace block_mad2api;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/weblib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * MAD Dashboard external API.
 */
class mad_dashboard extends external_api {

    public function __construct() {}

    /**
     * Returns the expected parameters for the enable function.
     *
     * @return external_function_parameters
    */
    public static function enable_parameters() {
        return new external_function_parameters([
            'courseId' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns the structure of the data returned by the enable function.
     *
     * @return external_multiple_structure
    */
    public static function enable_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'enabled' => new external_value(PARAM_BOOL, VALUE_DEFAULT, true),
                'url'     => new external_value(PARAM_TEXT, VALUE_DEFAULT, ''),
                'error'   => new external_value(PARAM_BOOL, VALUE_DEFAULT, false),
            ])
        );
    }

    /**
     * Enables the dashboard for a given course.
     *
     * @param int $courseId The ID of the course to enable the dashboard for.
     * @return array An array containing the status of the operation and the URL if successful.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function enable($courseId) {
        global $DB, $USER;

        $params = self::validate_parameters(self::enable_parameters(), ['courseId' => $courseId]);
        $courseId = (int)$params['courseId'];

        $dashboardSetting = $DB->get_record('mad2api_dashboard_settings', ['course_id' => $courseId]);

        if ($dashboardSetting && (int)$dashboardSetting->is_enabled === 1) {
            $response = self::api_dashboard_auth_url($courseId);

            if (empty($response) || !is_object($response) || !property_exists($response, 'url')) {
                return [['enabled' => false, 'url' => '', 'error' => true]];
            }
            return [['enabled' => true, 'url' => $response->url, 'error' => false]];
        }

        $databaseResponse = false;
        $response = self::api_enable_call($courseId);

        if ($response === null || !property_exists($response, 'url')) {
            return [['enabled' => false, 'url' => '', 'error' => true]];
        }

        $recordDashboardSettings = [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'course_id'  => $courseId,
            'is_enabled' => 1,
            'token'      => $USER->email,
        ];

        if (!empty($dashboardSetting->id)) {
            $recordDashboardSettings['id'] = $dashboardSetting->id;
            $databaseResponse = $DB->update_record('mad2api_dashboard_settings', $recordDashboardSettings, false);
        } else {
            $databaseResponse = (bool)$DB->insert_record('mad2api_dashboard_settings', $recordDashboardSettings, false);
        }

        $recordCourseLogs = [
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
            'course_id'    => $courseId,
            'status'       => 'todo',
            'last_log_page'=> 1,
            'students_sent'=> 0,
        ];

        $courseLog = $DB->get_record('mad2api_course_logs', ['course_id' => $courseId]);
        if (empty($courseLog->id)) {
            $DB->insert_record('mad2api_course_logs', $recordCourseLogs, false);
        }

        return [['enabled' => $databaseResponse, 'url' => $response->url, 'error' => false]];
    }

    /**
     * Returns the expected parameters for the disable function.
     *
     * @return external_function_parameters
    */
    public static function disable_parameters() {
        return new external_function_parameters([
            'courseId' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns the structure of the data returned by the disable function.
     *
     * @return external_multiple_structure
    */
    public static function disable_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'disabled' => new external_value(PARAM_BOOL, VALUE_REQUIRED)
            ])
        );
    }

    /**
     * Disables the dashboard for a given course.
     *
     * @param int $courseId The ID of the course to disable the dashboard for.
     * @return array An array containing the status of the operation.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function disable($courseId) {
        global $DB;

        $params = self::validate_parameters(self::disable_parameters(), ['courseId' => $courseId]);
        $courseId = (int)$params['courseId'];

        $dashboardSetting = $DB->get_record('mad2api_dashboard_settings', ['course_id' => $courseId]);

        $databaseResponse = false;
        if (!empty($dashboardSetting->id)) {
            $data = [
                'id'         => $dashboardSetting->id,
                'course_id'  => $courseId,
                'updated_at' => date('Y-m-d H:i:s'),
                'is_enabled' => 0
            ];
            $databaseResponse = $DB->update_record('mad2api_dashboard_settings', $data);
        }

        return [['disabled' => (bool)$databaseResponse]];
    }

    /**
     * Send pending activities names to API.
     *
     * @return void
    */
    public static function send_pending_activities() {
        global $DB;

        $response = self::api_check_pending_activities();

        if (empty($response->data)) {
            echo("No pending activities found \n");
            return;
        }

        echo("Found " . count($response->data) . " pending activities \n");

        foreach ($response->data as $activity) {
            if (empty($activity->contextInstanceId)) {
                continue;
            }

            $courseModule = $DB->get_record('course_modules', ['id' => (int)$activity->contextInstanceId]);

            if (empty($courseModule) || empty($courseModule->instance)) {
                echo("Course module not found for activity #{$activity->contextInstanceId} \n");

                continue;
            }

            $tableName = strtolower($activity->type);

            echo("Searching on table {{$tableName}} for activity #{$courseModule->instance} \n");

            $instance = $DB->get_record($tableName, ['id' => (int)$courseModule->instance]);

            if (empty($instance)) {
                echo("Instance not found for activity #{$activity->contextInstanceId} \n");
                continue;
            }

            echo("Sending activity name {$instance->name} for {$activity->name}\n");

            self::send_activity_name((int)$activity->moodleId, (int)$activity->contextId, $instance->name);
        }
    }

    /**
     * Send activity name to API.
     * @param int $courseId The ID of the course.
     * @param int $contextId The context ID of the activity.
     * @param string $name The name of the activity.
     * @return void
    */
    public static function send_activity_name($courseId, $contextId, $name) {
        self::do_put_request("api/v3/courses/{$courseId}/activities/{$contextId}", ['name' => $name]);
    }

    /**
     * Checks if the course data needs to be resent to the API and updates the course log accordingly.
     *
     * @param int $courseId The ID of the course to check.
     * @return void
    */
    public static function check_data_on_api($courseId) {
        global $DB;

        $lastlogs = array_slice($DB->get_records('mad2api_course_logs', ['course_id' => (int)$courseId, 'status' => 'done']), -1);
        $courseLog = !empty($lastlogs) ? $lastlogs[0] : null;

        if (!$courseLog) {
            echo("Course log not found for course #{$courseId} \n");
            return;
        }

        $response = self::api_check_course_data((int)$courseId);

        if ($response && !empty($response->resend_data)) {
            echo("Resend data enabled for course #{$courseId} \n");

            self::api_enable_call((int)$courseId);

            $updatedAttributes = [
                'id'            => $courseLog->id,
                'status'        => 'todo',
                'students_sent' => 0,
                'last_log_page' => 1,
                'updated_at'    => date('Y-m-d H:i:s')
            ];

            $DB->update_record('mad2api_course_logs', $updatedAttributes, false);
        }
    }

    /**
     * Verifies if the current user has a teacher role in the specified course context.
     * @param int $contextid The context ID of the course.
     * @return bool True if the user has a teacher role, false otherwise.
     */
    public static function is_current_user_course_teacher($contextid) {
        global $USER;

        $isPermitted = false;
        $permittedRoles = self::parse_role_ids_list((string)get_config('mad2api', 'roles'));

        foreach (self::get_user_roles($USER->id, $contextid) as $user_role) {
            if (in_array((int)$user_role->roleid, $permittedRoles, true)) {
                $isPermitted = true;
            }
        }

        return $isPermitted;
    }

    /**
     * Verifies if the current user has a coordinator role in the specified course context.
     * @param int $contextid The context ID of the course.
     * @return bool True if the user has a coordinator role, false otherwise.
    */
    public static function is_current_user_course_coordinator($contextid) {
        global $USER;

        $isPermitted = false;
        $permittedRoles = self::parse_role_ids_list((string)get_config('mad2api', 'admin_roles'));

        foreach (self::get_user_roles($USER->id, $contextid) as $user_role) {
            if (in_array((int)$user_role->roleid, $permittedRoles, true)) {
                $isPermitted = true;
            }
        }

        return $isPermitted;
    }

    /**
     * Gets the list of role IDs from a comma-separated string.
     * @param string $userid The user ID.
     * @param string $contextid The context ID.
     * @return array An array of role assignment records for the user in the specified context.
    */
    public static function get_user_roles($userid, $contextid) {
        global $DB;

        return $DB->get_records(
            'role_assignments',
            ['contextid' => (int)$contextid, 'userid' => (int)$userid]
        );
    }

    /**
     * Sends organization settings to API
     * @return void
    */
    private static function send_settings_to_api() {
        global $DB, $CFG;

        $apiSettings = $DB->get_records('mad2api_api_settings');
        $apiSetting = $apiSettings ? array_values($apiSettings)[0] : null;

        if (!$apiSetting || (substr((string)$apiSetting->sent_at, 0, 10) === date('Y-m-d'))) {
            return;
        }

        $updatedAttributes = [
            'id'         => $apiSetting->id,
            'sent_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $DB->update_record('mad2api_api_settings', $updatedAttributes, false);

        $settings = [
            'pluginVersion'  => \core_plugin_manager::instance()->get_plugin_info('block_mad2api')->release,
            'moodleVersion'  => $CFG->release,
        ];

        self::do_put_request('api/v2/settings/organizations/', $settings);
    }

    /**
     * Sends plugin installation data to API
     * @return void
    */
    public static function api_installation_call() {
        global $CFG;

        $settings = [
            'pluginVersion'   => \core_plugin_manager::instance()->get_plugin_info('block_mad2api')->release,
            'moodleVersion'   => $CFG->release,
            'installationDate'=> date('Y-m-d H:i:s')
        ];

        self::do_put_request('api/v2/settings/organizations/', $settings);
    }


    /**
     * Enables a course in the external API and sends necessary data.
     *
     * @param int $courseId The ID of the course to enable.
     * @return object|null The response data from the API or null on failure.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function api_enable_call($courseId) {
        global $USER, $DB;

        $courseId = (int)$courseId;
        $course = $DB->get_record('course', ['id' => $courseId], '*', MUST_EXIST);
        $courseUrl = new \moodle_url('/course/view.php', ['id' => $courseId]);

        $enable = [
            'course' => [
                'startDate' => $course->startdate,
                'endDate'   => $course->enddate,
                'name'      => $course->fullname,
                'shortName' => $course->shortname,
                'url'       => $courseUrl->out(),
            ],
            'teachers'      => self::get_course_teachers($courseId),
            'coordinators'  => self::get_course_coordinators($courseId),
            'currentUserId' => $USER->id ?? null,
        ];

        $auth = [
            'teacherId' => $USER->id,
            'moodleId'  => $courseId,
            'email'     => $USER->email,
        ];

        self::do_post_request("api/v3/courses/{$courseId}/enable", $enable);
        self::send_settings_to_api();

        $resp = self::do_post_request('api/v2/authorize', $auth);

        return $resp->data ?? null;
    }

    /**
     * Checks if the course data needs to be resent to the API.
     * @param int $courseId The ID of the course to check.
     * @return object|null The response data from the API or null on failure.
    */
    public static function api_check_course_data($courseId) {
        return self::do_get_request("api/v2/plugin/courses/{$courseId}/resend_data");
    }

    /**
     * Checks for pending activities.
     * @return object|null The response data from the API or null on failure.
    */
    public static function api_check_pending_activities() {
        return self::do_get_request('api/v3/activities/pending_information');
    }

    /**
     * Sends the list of students enrolled in a course to the external API in batches.
     * @param int $courseId The ID of the course.
     * @return void
    */
    public static function api_send_students($courseId) {
        global $DB, $USER;

        $courseId = (int)$courseId;

        $courseLog = $DB->get_record('mad2api_course_logs', ['course_id' => $courseId, 'students_sent' => 1]);

        if ($courseLog) {
            return;
        }

        $count = self::get_course_students_count($courseId);
        $perPage = 20;
        $endPage = (int)ceil($count / $perPage);

        for ($currentPage = 1; $currentPage <= $endPage; $currentPage++) {
            $offset = ($currentPage - 1) * $perPage;

            $data = [
                'students' => self::get_course_students($courseId, $perPage, $offset)
            ];

            self::do_post_request("api/v2/courses/{$courseId}/students/batch", $data, $courseId);
        }

        $courseLog = $DB->get_record('mad2api_course_logs', ['course_id' => $courseId]);

        if ($courseLog) {
            $updatedAttributes = [
                'id'            => $courseLog->id,
                'students_sent' => 1,
                'updated_at'    => date('Y-m-d H:i:s')
            ];
            $DB->update_record('mad2api_course_logs', $updatedAttributes, false);
        }
    }


    /**
     * Sends the course logs to the external API in batches.
     * @param int $courseId The ID of the course.
     * @return void
    */
    public static function api_send_logs($courseId) {
        global $DB;

        $courseId = (int)$courseId;

        $courseLog = $DB->get_record('mad2api_course_logs', ['course_id' => $courseId, 'status' => 'done']);

        if ($courseLog) { return; }

        $courseLog = $DB->get_record('mad2api_course_logs', ['course_id' => $courseId]);

        $count = $DB->count_records_sql("
            SELECT COUNT(DISTINCT m.id)
              FROM {logstore_standard_log} m
             WHERE m.courseid = :courseid
        ", ['courseid' => $courseId]);

        $perPage = 100;
        $endPage = (int)ceil($count / $perPage);

        echo("Sending {$count} logs \n");

        $startPage = !empty($courseLog->last_log_page) ? (int)$courseLog->last_log_page : 1;

        for ($currentPage = $startPage; $currentPage <= $endPage; $currentPage++) {
            if ($courseLog) {
                $updatedAttributes = [
                    'id'            => $courseLog->id,
                    'last_log_page' => $currentPage,
                    'updated_at'    => date('Y-m-d H:i:s')
                ];
                $DB->update_record('mad2api_course_logs', $updatedAttributes, false);
            }

            $offset = ($currentPage - 1) * $perPage;

            $logs = $DB->get_records_sql("
                SELECT m.*
                  FROM {logstore_standard_log} m
                 WHERE m.courseid = :courseid
                 GROUP BY m.id
            ", ['courseid' => $courseId], $offset, $perPage);

            foreach ($logs as $id => $log) {
                if ($log->eventname !== '\core\event\course_module_created') {
                    continue;
                }

                $cm = null;

                if (!empty($log->contextinstanceid)) {
                    $cm = get_coursemodule_from_id(false, $log->contextinstanceid, 0, false, IGNORE_MISSING);
                }

                if (!$cm && !empty($log->other)) {
                    $otherObj = json_decode($log->other);

                    if ($otherObj) {
                        $modname    = $otherObj->modulename ?? ($otherObj->module ?? null);
                        $instanceid = $otherObj->instanceid ?? ($otherObj->id ?? null);

                        if ($modname && $instanceid) {
                            $cm = get_coursemodule_from_instance($modname, $instanceid, $courseId, false, IGNORE_MISSING);
                        }
                    }
                }

                if ($cm) {
                    $grades = grade_get_grades($cm->course, 'mod', $cm->modname, $cm->instance);
                    $gradable = !empty($grades->items);

                    $activityUrlOut = (new \moodle_url("/mod/{$cm->modname}/view.php", ['id' => $cm->id]))->out();

                    $log->gradable    = $gradable ? 1 : 0;
                    $log->activityurl = $activityUrlOut;

                    $other = json_decode($log->other, true) ?: [];
                    $other['gradable'] = $log->gradable;
                    $other['url']      = $log->activityurl;
                    $log->other        = json_encode($other);
                } else {
                    $log->gradable = null;
                    $log->url      = null;
                }

                $logs[$id] = $log;
            }

            $data = ['logs' => $logs];

            echo("Sending page {$currentPage} with " . count($logs) . " logs \n");

            $response = self::do_post_request("api/v2/courses/{$courseId}/logs/batch", $data, $courseId);

            if (!empty($response->error)) {
                echo("Error sending logs: " . json_encode($response) . "\n");
            }
        }

        self::send_original_course_logs($courseId);
        self::send_grades($courseId);
    }

    /**
     * Sends the grades of the course students to the external API in batches.
     * @param int $courseid The ID of the course.
     * @return void
    */
    public static function send_grades($courseid) {
        global $DB, $USER;

        $courseid = (int)$courseid;

        echo("sending activities \n");

        $count = $DB->count_records('grade_items', [
            'courseid' => $courseid,
            'itemtype' => 'mod'
        ]);

        $perPage = 25;
        $endPage = (int)ceil($count / $perPage);
        $url = "api/v2/courses/{$courseid}/events";

        echo("Sending {$count} grade items for course {$courseid} in {$endPage} pages\n");

        for ($currentPage = 1; $currentPage <= $endPage; $currentPage++) {
            $offset = ($currentPage - 1) * $perPage;

            $gradeitems = $DB->get_records_sql("
                SELECT *
                  FROM {grade_items}
                 WHERE courseid = :courseid AND itemtype = 'mod'
            ", ['courseid' => $courseid], $offset, $perPage);

            foreach ($gradeitems as $item) {
                $modname = $item->itemmodule;
                $tm = $DB->get_manager();

                if ($modname === 'hvp' && !$tm->table_exists('hvp')) {
                    $modname = 'h5pactivity';
                }

                $cm = get_coursemodule_from_instance($modname, $item->iteminstance, $courseid, false, IGNORE_MISSING);

                if (!$cm) {
                    continue;
                }

                $context = \context_module::instance($cm->id);

                $perPageItemPage = 25;
                $countItemPage = $DB->count_records('grade_grades', ['itemid' => $item->id]);
                $endItemPage = (int)ceil($countItemPage / $perPageItemPage);

                for ($currentItemPage = 1; $currentItemPage <= $endItemPage; $currentItemPage++) {
                    $offsetItemPage = ($currentItemPage - 1) * $perPageItemPage;

                    $grades = $DB->get_records_sql("
                        SELECT *
                          FROM {grade_grades}
                         WHERE itemid = :itemid
                    ", ['itemid' => $item->id], $offsetItemPage, $perPageItemPage);

                    foreach ($grades as $grade) {
                        $data = [
                            'other' => [
                                'itemname'    => $item->itemname,
                                'itemtype'    => $item->itemtype,
                                'item_module' => $item->itemmodule,
                                'instance_id' => $context->instanceid,
                                'finalgrade'  => $grade->finalgrade
                            ],
                            'action'               => 'created',
                            'target'               => 'grade_item',
                            'moodle_id'            => $courseid,
                            'moodle_user_id'       => $USER->id,
                            'moodle_related_user_id'=> $grade->userid,
                            'component'            => 'core',
                            'event_name'           => '\core\event\user_graded',
                            'time_created'         => $item->timemodified ?? time(),
                            'context_id'           => $context->id,
                        ];

                        $data['raw_data'] = $data;

                        self::do_post_request($url, $data, $courseid);
                    }
                }
            }
        }
    }

    /**
     * Sends the original course logs to the external API in batches.
     * @param int $courseId The ID of the course.
     * @return void
    */
    public static function send_original_course_logs($courseId) {
        global $DB, $USER;

        $courseId = (int)$courseId;

        echo("Sending original course logs for {$courseId}\n");

        $count = $DB->count_records_sql("
            SELECT COUNT(DISTINCT cm.id)
              FROM {course_modules} cm
              JOIN {modules} m ON cm.module = m.id
             WHERE cm.course = :courseid
        ", ['courseid' => $courseId]);

        $perPage = 25;
        $endPage = (int)ceil($count / $perPage);

        echo("Sending {$count} activities for course {$courseId} in {$endPage} pages\n");

        for ($currentPage = 1; $currentPage <= $endPage; $currentPage++) {
            $offset = ($currentPage - 1) * $perPage;
            $logs = [];

            echo("Sending page {$currentPage} for course {$courseId} \n");

            $courseModules = $DB->get_records_sql("
                SELECT cm.id AS course_module_id,
                       m.name AS module_type,
                       cm.instance,
                       cm.section,
                       cm.visible
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id
                 WHERE cm.course = :courseid
            ", ['courseid' => $courseId], $offset, $perPage);

            if (empty($courseModules)) {
                echo("No course modules found for course {$courseId} \n");
                continue;
            }

            echo("Found " . count($courseModules) . " course modules for course {$courseId} \n");

            foreach ($courseModules as $courseModule) {
                $tableName = $courseModule->module_type;
                $instanceId = (int)$courseModule->instance;

                $instance = $DB->get_record($tableName, ['id' => $instanceId]);

                if (empty($instance) || !isset($instance->name)) {
                    echo("Instance not found for table {$tableName} with ID {$instanceId}\n");
                    continue;
                }

                $context = \context_module::instance($courseModule->course_module_id, IGNORE_MISSING);

                if (empty($context) || !isset($context->instanceid)) {
                    echo("Context not found for activity #{$courseModule->course_module_id} \n");
                    continue;
                }

                $cm = get_coursemodule_from_id(false, $courseModule->course_module_id, 0, false, MUST_EXIST);
                $modname = $cm->modname;

                $tm = $DB->get_manager();

                if ($modname === 'hvp' && !$tm->table_exists('hvp')) {
                    $modname = 'h5pactivity';
                }

                $grades = grade_get_grades($cm->course, 'mod', $cm->modname, $cm->instance);
                $activityUrl = new \moodle_url("/mod/{$cm->modname}/view.php", ['id' => $cm->id]);

                $logs[$context->id] = [
                    'id'                => 0,
                    'crud'              => 'c',
                    'other'             => json_encode([
                        'name'       => $instance->name,
                        'instanceid' => $context->instanceid,
                        'modulename' => $tableName,
                        'visible'    => $courseModule->visible,
                        'gradable'   => !empty($grades->items),
                        'duedate'    => self::get_activity_duedate($cm),
                        'url'        => $activityUrl->out()
                    ]),
                    'action'            => 'created',
                    'target'            => 'course_module',
                    'courseid'          => $courseId,
                    'userid'            => $USER->id,
                    'objectid'          => $context->instanceid,
                    'anonymous'         => 0,
                    'component'         => 'core',
                    'contextid'         => $context->id,
                    'eventname'         => '\core\event\course_module_created',
                    'objecttable'       => 'course_modules',
                    'contextlevel'      => $context->contextlevel,
                    'contextinstanceid' => $context->instanceid,
                    'timecreated'       => $instance->timemodified ?? time()
                ];

                echo("Sending activity {$instance->name} | {$instanceId} | visible? {$courseModule->visible} \n");
            }

            $data = ['logs' => $logs];

            try {
                self::do_post_request("api/v2/courses/{$courseId}/logs/batch", $data, $courseId);
            } catch (\Exception $e) {
                echo("Error sending logs: " . $e->getMessage() . "\n");
            }
        }
    }

    /**
     * Retrieves the due date of an activity based on its course module.
     *
     * @param \stdClass $cm The course module object.
     * @return int|null The due date timestamp or null if not applicable.
    */
    public static function get_activity_duedate($cm) {
        global $DB;

        if (empty($cm->modname) || empty($cm->instance)) {
            return null;
        }

        $modname    = $cm->modname;
        $instanceid = (int)$cm->instance;

        $duedatefields = [
            'assign'        => 'duedate',
            'quiz'          => 'timeclose',
            'lesson'        => 'deadline',
            'workshop'      => 'submissionend',
            'chat'          => 'chattime',
            'data'          => 'timeavailableto',
            'feedback'      => 'timeclose',
            'forum'         => 'duedate',
            'glossary'      => 'assesseduntil',
            'scorm'         => 'timeclose',
            'survey'        => null,
            'wiki'          => null,
            'h5pactivity'   => 'timeclose',
            'choice'        => 'timeclose',
            'database'      => 'timeavailableto',
            'assignoverride'=> 'duedate',
        ];

        if (empty($duedatefields[$modname])) {
            return null;
        }

        $field  = $duedatefields[$modname];
        $record = $DB->get_record($modname, ['id' => $instanceid], $field);

        return $record->$field ?? null;
    }

    /**
     * Requests an authorization URL from the external API for the dashboard.
     *
     * @param int $courseId The ID of the course.
     * @return object An object containing the authorization URL or an empty object on failure.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function api_dashboard_auth_url($courseId) {
        global $USER;

        $courseId = (int)$courseId;
        $auth = [
            'teacherId' => $USER->id,
            'moodleId'  => $courseId,
            'email'     => $USER->email
        ];

        $resp = self::do_post_request('api/v2/authorize', $auth, $courseId);

        return $resp->data ?? (object)[];
    }

    /**
     * Checks if a user is enrolled in any monitored courses.
     *
     * @param int $userId The ID of the user to check.
     * @return object|null The course record if the user is enrolled in a monitored course, null otherwise.
    */
    public static function enrolled_monitored_courses($userId) {
        global $DB;

        $userId = (int)$userId;

        $monitoredCourses = $DB->get_records('mad2api_dashboard_settings', ['is_enabled' => 1]);

        foreach ($monitoredCourses as $monitoredCourse) {
            $courseIdRow = $DB->get_record_sql("
                SELECT c.id
                  FROM {course} c
                  JOIN {context} ct ON c.id = ct.instanceid
                  JOIN {role_assignments} ra ON ra.contextid = ct.id
                  JOIN {user} u ON u.id = ra.userid
                 WHERE c.id = :courseid AND u.id = :userid
            ", ['courseid' => (int)$monitoredCourse->course_id, 'userid' => $userId]);

            if (!empty($courseIdRow)) {
                return $courseIdRow;
            }
        }

        return null;
    }

    /**
     * Gets the total number of students enrolled in a course.
     * @param int $courseId The ID of the course.
     * @return int The total number of students enrolled in the course.
     */
    public static function get_course_students_count($courseId) {
        global $DB;

        $courseId   = (int)$courseId;
        $studentRole= (int)get_config('mad2api', 'studentRole');

        return $DB->count_records_sql("
            SELECT COUNT(*)
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
             WHERE c.id = :courseid AND r.id = :roleid
        ", ['courseid' => $courseId, 'roleid' => $studentRole]);
    }

    /**
     * Retrieves a paginated list of students enrolled in a course along with their details.
     *
     * @param int $courseId The ID of the course.
     * @param int $perPage The number of students to retrieve per page.
     * @param int $offset The offset for pagination.
     * @return array An array of student records with their details.
    */
    public static function get_course_students($courseId, $perPage, $offset) {
        global $DB;

        $courseId    = (int)$courseId;
        $perPage     = (int)$perPage;
        $offset      = (int)$offset;
        $studentRole = (int)get_config('mad2api', 'studentRole');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   (CASE WHEN u.lastaccess = '0' THEN 'false' ELSE 'true' END) AS logged_in,
                   AVG(g.rawgrade) AS current_grade,
                   u.phone1, u.phone2
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
         LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid IN (
                    SELECT gi.id FROM {grade_items} gi WHERE gi.courseid = :courseid_gi
              )
             WHERE c.id = :courseid AND r.id = :roleid
          GROUP BY u.id
        ";

        $params = [
            'courseid_gi' => $courseId,
            'courseid'    => $courseId,
            'roleid'      => $studentRole
        ];

        $students = $DB->get_records_sql($sql, $params, $offset, $perPage);

        return self::camelizeArray($students);
    }

    /**
     * Retrieves details of a specific student enrolled in a course.
     *
     * @param int $courseId The ID of the course.
     * @param int $studentId The ID of the student.
     * @return object|null An object containing the student's details or null if not found.
    */
    public static function get_course_student($courseId, $studentId) {
        global $DB;

        $courseId    = (int)$courseId;
        $studentId   = (int)$studentId;
        $studentRole = (int)get_config('mad2api', 'studentRole');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   (CASE WHEN u.lastaccess = '0' THEN 'false' ELSE 'true' END) AS logged_in,
                   AVG(g.rawgrade) AS current_grade
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
         LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid IN (
                    SELECT gi.id FROM {grade_items} gi WHERE gi.courseid = :courseid_gi
              )
             WHERE c.id = :courseid AND r.id = :roleid AND u.id = :userid
        ";

        $params = [
            'courseid_gi' => $courseId,
            'courseid'    => $courseId,
            'roleid'      => $studentRole,
            'userid'      => $studentId,
        ];

        $student = $DB->get_record_sql($sql, $params);

        return self::camelizeObject($student);
    }

    /**
     * Retrieves details of a specific user in the context of a course, including their role.
     *
     * @param int $userId The ID of the user.
     * @param int $courseId The ID of the course.
     * @return object|null An object containing the user's details and role or null if not found.
    */
    public static function get_user($userId, $courseId) {
        global $DB;

        $userId     = (int)$userId;
        $courseId   = (int)$courseId;
        $studentRole = (int)get_config('mad2api', 'studentRole');

        $coordinatorRolesCfg = (string)get_config('mad2api', 'admin_roles');
        $coordinatorRoles = self::parse_role_ids_list($coordinatorRolesCfg);
        list($inSql, $inParams) = $DB->get_in_or_equal($coordinatorRoles, SQL_PARAMS_NAMED, 'cr');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   (CASE WHEN u.lastaccess = '0' THEN 'false' ELSE 'true' END) AS logged_in,
                   AVG(g.rawgrade) AS current_grade, u.phone1, u.phone2,
                   r.shortname AS moodle_role,
                   (CASE
                        WHEN r.id = :studentrole THEN 'student'
                        WHEN r.id {$inSql} THEN 'coordinator'
                        ELSE 'teacher'
                    END) AS role
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
         LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid IN (
                    SELECT gi.id FROM {grade_items} gi WHERE gi.courseid = :courseid_gi
              )
             WHERE c.id = :courseid AND u.id = :userid
        ";

        $params = array_merge([
            'studentrole' => $studentRole,
            'courseid_gi' => $courseId,
            'courseid'    => $courseId,
            'userid'      => $userId,
        ], $inParams);

        $user = $DB->get_record_sql($sql, $params);

        return self::camelizeObject($user);
    }

    /**
     * Retrieves a list of teachers assigned to a specific course.
     * @param int $courseId The ID of the course.
     * @return array An array of teacher records with their details.
    */
    public static function get_course_teachers($courseId) {
        global $DB;

        $courseId = (int)$courseId;

        $rolesCfg = (string)get_config('mad2api', 'roles');
        $roleIds  = self::parse_role_ids_list($rolesCfg);
        if (empty($roleIds)) { return []; }

        list($inSql, $inParams) = $DB->get_in_or_equal($roleIds, SQL_PARAMS_NAMED, 'tr');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   r.shortname AS moodle_role, u.phone1, u.phone2
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
             WHERE c.id = :courseid AND r.id {$inSql}
        ";

        $params = array_merge(['courseid' => $courseId], $inParams);

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Retrieves a list of coordinators assigned to a specific course.
     * @param int $courseId The ID of the course.
     * @return array An array of coordinator records with their details.
    */
    public static function get_course_coordinators($courseId) {
        global $DB;

        $courseId = (int)$courseId;

        $rolesCfg = (string)get_config('mad2api', 'admin_roles');
        $roleIds  = self::parse_role_ids_list($rolesCfg);
        if (empty($roleIds)) { return []; }

        list($inSql, $inParams) = $DB->get_in_or_equal($roleIds, SQL_PARAMS_NAMED, 'cr');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   r.shortname AS moodle_role, u.phone1, u.phone2
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
             WHERE c.id = :courseid AND r.id {$inSql}
        ";

        $params = array_merge(['courseid' => $courseId], $inParams);

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Converts the keys of an object from snake_case to camelCase.
     * @param object|array $obj The object or associative array to convert.
     * @return array The new associative array with camelCase keys.
    */
    public static function camelizeObject($obj) {
        $new_obj = [];

        if (gettype($obj) === 'boolean' || $obj === null) {
            return $new_obj;
        }

        foreach ($obj as $key => $value) {
            $new_obj[self::convertToCamel($key, '_')] = $value;
        }

        return $new_obj;
    }

    /**
     * Converts an array of objects from snake_case to camelCase keys.
     * @param array $array The array of objects to convert.
     * @return array The new array with camelCase keys.
    */
    public static function camelizeArray($array) {
        $formattedArray = [];

        foreach ($array as $item) {
            $formattedArray[] = self::camelizeObject($item);
        }

        return $formattedArray;
    }

    /**
     * Parses a comma-separated string of role IDs into an array of integers.
     * @param string $csv The comma-separated string of role IDs.
     * @return array An array of integers representing the role IDs.
     */
    private static function parse_role_ids_list(string $csv): array {
        if ($csv === '') { return []; }

        return array_values(array_filter(array_map('intval', preg_split('/\s*,\s*/', $csv))));
    }

    /**
     * Disables a course in the local database if the external API returns a 400 or 404 status code.
     * @param resource $ch The cURL handle.
     * @param int $courseId The ID of the course to potentially disable.
     * @return void
    */
    private static function disable_course_if_not_found($ch, $courseId) {
        global $DB;

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (in_array((int)$http_status, [400, 404], true)) {
            $resources = $DB->get_records(
                'mad2api_dashboard_settings', ['course_id' => (int)$courseId, 'is_enabled' => 1]
            );

            foreach ($resources as $resource) {
                $data = [
                    'id'         => $resource->id,
                    'is_enabled' => 0
                ];

                $DB->update_record('mad2api_dashboard_settings', $data, false);
            }
        }
    }

    /**
     * Converts a string to camelCase based on a specified delimiter.
     * @param string $str The input string to convert.
     * @param string $delim The delimiter used to split the string.
     * @return string The converted camelCase string.
    */
    private static function convertToCamel($str, $delim) {
        $parts = explode($delim, (string)$str);
        $parts = array_map('ucwords', $parts);

        return lcfirst(implode('', $parts));
    }

    public static function is_course_enabled($courseId) {
        global $DB;

        return (bool)$DB->get_record('mad2api_dashboard_settings', [
            'course_id'  => (int)$courseId,
            'is_enabled' => 1
        ]);
    }

    /**
     * Sends a POST request to the specified URL with the given body and handles course disabling if necessary.
     * @param string $url The endpoint URL (relative to the base API URL).
     * @param array|object $body The data to send in the POST request.
     * @param int|null $courseId The ID of the course (optional, used for disabling if not found).
     * @return object|null The response data from the API or null on failure.
    */
    public static function do_post_request($url, $body, $courseId = null) {
        $apiKey = get_config('mad2api', 'api_key');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            'accept: application/json',
            'Content-Type: application/json',
            "API-KEY: {$apiKey}"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($courseId !== null) {
            self::disable_course_if_not_found($ch, (int)$courseId);
        }

        curl_close($ch);

        return json_decode($response ?? '');
    }

    /**
     * Sends a PUT request to the specified URL with the given body.
     * @param string $url The endpoint URL (relative to the base API URL).
     * @param array|object $body The data to send in the PUT request.
     * @return object|null The response data from the API or null on failure.
    */
    private static function do_put_request($url, $body) {
        $apiKey = get_config('mad2api', 'api_key');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            'accept: application/json',
            'Content-Type: application/json',
            "API-KEY: {$apiKey}"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response ?? '');
    }

    /**
     * Sends a GET request to the specified URL.
     * @param string $url The endpoint URL (relative to the base API URL).
     * @return object|null The response data from the API or null on failure.
    */
    private static function do_get_request($url) {
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

        return json_decode($response ?? '');
    }

    /**
     * Constructs the full URL for the API endpoint.
     * @param string $path The endpoint path (relative to the base API URL).
     * @return string The full URL for the API endpoint.
    */
    private static function get_url_for($path) {
        $apiUrl = rtrim((string)get_config('mad2api', 'api_url'), '/');
        $path   = ltrim($path, '/');

        return "{$apiUrl}/{$path}";
    }
}
