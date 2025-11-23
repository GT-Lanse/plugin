<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * MAD Dashboard external API.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
            'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
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
     * @param int $courseid The ID of the course to enable the dashboard for.
     * @return array An array containing the status of the operation and the URL if successful.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function enable($courseid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::enable_parameters(), ['courseid' => $courseid]);
        $courseid = (int)$params['courseid'];

        $dashboardsetting = $DB->get_record('block_mad2api_dashboard_settings', ['courseid' => $courseid]);

        if ($dashboardsetting && (int)$dashboardsetting->isenabled === 1) {
            $response = self::api_dashboard_auth_url($courseid);

            if (empty($response) || !is_object($response) || !property_exists($response, 'url')) {
                return [['enabled' => false, 'url' => '', 'error' => true]];
            }

            return [['enabled' => true, 'url' => $response->url, 'error' => false]];
        }

        $databaseresponse = false;
        $response = self::api_enable_call($courseid);

        if ($response === null || !property_exists($response, 'url')) {
            return [['enabled' => false, 'url' => '', 'error' => true]];
        }

        $recorddatabasesettings = [
            'createdat' => date('Y-m-d H:i:s'),
            'updatedat' => date('Y-m-d H:i:s'),
            'courseid'  => $courseid,
            'isenabled' => 1,
            'token'      => $USER->email,
        ];

        if (!empty($dashboardsetting->id)) {
            $recorddatabasesettings['id'] = $dashboardsetting->id;
            $databaseresponse = $DB->update_record('block_mad2api_dashboard_settings', $recorddatabasesettings, false);
        } else {
            $databaseresponse = (bool)$DB->insert_record('block_mad2api_dashboard_settings', $recorddatabasesettings, false);
        }

        $recordcourselog = [
            'createdat'   => date('Y-m-d H:i:s'),
            'updatedat'   => date('Y-m-d H:i:s'),
            'courseid'    => $courseid,
            'status'       => 'todo',
            'lastlogpage'=> 1,
            'studentssent'=> 0,
        ];

        $courselog = $DB->get_record('block_mad2api_course_logs', ['courseid' => $courseid]);

        if (empty($courselog->id)) {
            $DB->insert_record('block_mad2api_course_logs', $recordcourselog, false);
        }

        return [['enabled' => $databaseresponse, 'url' => $response->url, 'error' => false]];
    }

    /**
     * Returns the expected parameters for the disable function.
     *
     * @return external_function_parameters
    */
    public static function disable_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
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
     * @param int $courseid The ID of the course to disable the dashboard for.
     * @return array An array containing the status of the operation.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function disable($courseid) {
        global $DB;

        $params = self::validate_parameters(self::disable_parameters(), ['courseid' => $courseid]);
        $courseid = (int)$params['courseid'];

        $dashboardSetting = $DB->get_record('block_mad2api_dashboard_settings', ['courseid' => $courseid]);

        $databaseresponse = false;
        if (!empty($dashboardSetting->id)) {
            $data = [
                'id'         => $dashboardSetting->id,
                'courseid'  => $courseid,
                'updatedat' => date('Y-m-d H:i:s'),
                'isenabled' => 0
            ];
            $databaseresponse = $DB->update_record('block_mad2api_dashboard_settings', $data);
        }

        return [['disabled' => (bool)$databaseresponse]];
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
            mtrace("No pending activities found \n");

            return;
        }

        mtrace("Found " . count($response->data) . " pending activities \n");

        foreach ($response->data as $activity) {
            if (empty($activity->contextInstanceId)) {
                continue;
            }

            $coursemodule = $DB->get_record('course_modules', ['id' => (int)$activity->contextInstanceId]);

            if (empty($coursemodule) || empty($coursemodule->instance)) {
                mtrace("Course module not found for activity #{$activity->contextInstanceId} \n");

                continue;
            }

            $tablename = strtolower($activity->type);

            mtrace("Searching on table {{$tablename}} for activity #{$coursemodule->instance} \n");

            $instance = $DB->get_record($tablename, ['id' => (int)$coursemodule->instance]);

            if (empty($instance)) {
                mtrace("Instance not found for activity #{$activity->contextInstanceId} \n");

                continue;
            }

            mtrace("Sending activity name {$instance->name} for {$activity->name}\n");

            self::send_activity_name((int)$activity->moodleId, (int)$activity->contextId, $instance->name);
        }
    }

    /**
     * Send activity name to API.
     * @param int $courseid The ID of the course.
     * @param int $contextid The context ID of the activity.
     * @param string $name The name of the activity.
     * @return void
    */
    public static function send_activity_name($courseid, $contextid, $name) {
        self::do_put_request("api/v3/courses/{$courseid}/activities/{$contextid}", ['name' => $name]);
    }

    /**
     * Checks if the course data needs to be resent to the API and updates the course log accordingly.
     *
     * @param int $courseid The ID of the course to check.
     * @return void
    */
    public static function check_data_on_api($courseid) {
        global $DB;

        $lastlogs = array_slice($DB->get_records('block_mad2api_course_logs', ['courseid' => (int)$courseid, 'status' => 'done']), -1);
        $courselog = !empty($lastlogs) ? $lastlogs[0] : null;

        if (!$courselog) {
            mtrace("Course log not found for course #{$courseid} \n");

            return;
        }

        $response = self::api_check_course_data((int)$courseid);

        if ($response && !empty($response->resend_data)) {
            mtrace("Resend data enabled for course #{$courseid} \n");

            self::api_enable_call((int)$courseid);

            $updatedattributes = [
                'id'            => $courselog->id,
                'status'        => 'todo',
                'studentssent' => 0,
                'lastlogpage' => 1,
                'updatedat'    => date('Y-m-d H:i:s')
            ];

            $DB->update_record('block_mad2api_course_logs', $updatedattributes, false);
        }
    }

    /**
     * Verifies if the current user has a teacher role in the specified course context.
     * @param int $contextid The context ID of the course.
     * @return bool True if the user has a teacher role, false otherwise.
     */
    public static function is_current_user_course_teacher($contextid) {
        global $USER;

        $ispermitted = false;
        $permittedroles = self::parse_role_ids_list((string)get_config('block_mad2api', 'roles'));

        foreach (self::get_user_roles($USER->id, $contextid) as $user_role) {
            if (in_array((int)$user_role->roleid, $permittedroles, true)) {
                $ispermitted = true;
            }
        }

        return $ispermitted;
    }

    /**
     * Verifies if the current user has a coordinator role in the specified course context.
     * @param int $contextid The context ID of the course.
     * @return bool True if the user has a coordinator role, false otherwise.
    */
    public static function is_current_user_course_coordinator($contextid) {
        global $USER;

        $ispermitted = false;
        $permittedroles = self::parse_role_ids_list((string)get_config('block_mad2api', 'adminroles'));

        foreach (self::get_user_roles($USER->id, $contextid) as $user_role) {
            if (in_array((int)$user_role->roleid, $permittedroles, true)) {
                $ispermitted = true;
            }
        }

        return $ispermitted;
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

        $apisettings = $DB->get_records('block_mad2api_api_settings');
        $apisetting = $apisettings ? array_values($apisettings)[0] : null;

        if (!$apisetting || (substr((string)$apisetting->sentat, 0, 10) === date('Y-m-d'))) {
            return;
        }

        $updatedattributes = [
            'id'         => $apisetting->id,
            'sentat'    => date('Y-m-d H:i:s'),
            'updatedat' => date('Y-m-d H:i:s')
        ];
        $DB->update_record('block_mad2api_api_settings', $updatedattributes, false);

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
     * @param int $courseid The ID of the course to enable.
     * @return object|null The response data from the API or null on failure.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function api_enable_call($courseid) {
        global $USER, $DB;

        $courseid = (int)$courseid;
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);

        $enable = [
            'course' => [
                'startDate' => (int)($course->startdate ?? 0),
                'endDate'   => (int)($course->enddate   ?? 0),
                'name'      => $course->fullname  ?? '',
                'shortName' => $course->shortname ?? '',
                'url'       => $courseurl->out()
            ],
            'teachers'      => self::get_course_teachers($courseid),
            'coordinators'  => self::get_course_coordinators($courseid),
            'currentUserId' => $USER->id ?? null,
        ];

        $auth = [
            'teacherId' => $USER->id,
            'moodleId'  => $courseid,
            'email'     => $USER->email,
        ];

        self::do_post_request("api/v3/courses/{$courseid}/enable", $enable);
        self::send_settings_to_api();

        $resp = self::do_post_request('api/v2/authorize', $auth);

        return $resp->data ?? null;
    }

    /**
     * Checks if the course data needs to be resent to the API.
     * @param int $courseid The ID of the course to check.
     * @return object|null The response data from the API or null on failure.
    */
    public static function api_check_course_data($courseid) {
        return self::do_get_request("api/v2/plugin/courses/{$courseid}/resend_data");
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
     * @param int $courseid The ID of the course.
     * @return void
    */
    public static function api_send_students($courseid) {
        global $DB, $USER;

        $courseid = (int)$courseid;

        $courselog = $DB->get_record('block_mad2api_course_logs', ['courseid' => $courseid, 'studentssent' => 1]);

        if ($courselog) {
            return;
        }

        $count = self::get_course_students_count($courseid);
        $perpage = 20;
        $endpage = (int)ceil($count / $perpage);

        for ($currentpage = 1; $currentpage <= $endpage; $currentpage++) {
            $offset = ($currentpage - 1) * $perpage;

            $data = [
                'students' => self::get_course_students($courseid, $perpage, $offset)
            ];

            self::do_post_request("api/v2/courses/{$courseid}/students/batch", $data, $courseid);
        }

        $courselog = $DB->get_record('block_mad2api_course_logs', ['courseid' => $courseid]);

        if ($courselog) {
            $updatedattributes = [
                'id'            => $courselog->id,
                'studentssent' => 1,
                'updatedat'    => date('Y-m-d H:i:s')
            ];

            $DB->update_record('block_mad2api_course_logs', $updatedattributes, false);
        }
    }

    /**
     * Sends the course logs to the external API in batches.
     * @param int $courseid The ID of the course.
     * @return void
    */
    public static function api_send_logs($courseid) {
        global $DB, $USER;

        $courseid = (int)$courseid;

        $courselog = $DB->get_record('block_mad2api_course_logs', [
            'courseid' => $courseid,
            'status'   => 'done'
        ]);

        if ($courselog) {
            mtrace("Logs para o curso {$courseid} já foram enviados anteriormente.\n");
            return;
        }

        $courselog = $DB->get_record('block_mad2api_course_logs', ['courseid' => $courseid]);

        $count = $DB->count_records('logstore_standard_log', ['courseid' => $courseid]);
        $perpage = 100;
        $endpage = (int)ceil($count / $perpage);

        mtrace("Enviando {$count} logs para o curso {$courseid} ({$endpage} páginas)\n");

        $startpage = (!empty($courselog) && !empty($courselog->lastlogpage))
            ? (int)$courselog->lastlogpage
            : 1;

        for ($currentpage = $startpage; $currentpage <= $endpage; $currentpage++) {

            $offset = ($currentpage - 1) * $perpage;

            $logs = $DB->get_records_sql("
                SELECT m.*
                FROM {logstore_standard_log} m
                WHERE m.courseid = :courseid
                ORDER BY m.id ASC
            ", ['courseid' => $courseid], $offset, $perpage);

            mtrace("Enviando página {$currentpage} com " . count($logs) . " logs \n");

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
                            $cm = get_coursemodule_from_instance($modname, $instanceid, $courseid, false, IGNORE_MISSING);
                        }
                    }
                }

                if ($cm) {
                    $grades   = grade_get_grades($cm->course, 'mod', $cm->modname, $cm->instance);
                    $gradable = !empty($grades->items);

                    $activityurlout = (new \moodle_url("/mod/{$cm->modname}/view.php", ['id' => $cm->id]))->out();

                    $log->gradable    = $gradable ? 1 : 0;
                    $log->activityurl = $activityurlout;

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

            $response = self::do_post_request("api/v2/courses/{$courseid}/logs/batch", $data, $courseid);

            if (!empty($response->error)) {
                mtrace("Erro ao enviar logs (página {$currentpage}): " . json_encode($response) . "\n");

                return;
            }

            if ($courselog) {
                $updatedattributes = [
                    'id'          => $courselog->id,
                    'lastlogpage' => $currentpage + 1,
                    'updatedat'   => date('Y-m-d H:i:s')
                ];
                $DB->update_record('block_mad2api_course_logs', $updatedattributes, false);
                $courselog->lastlogpage = $currentpage + 1;
            }
        }

        if ($courselog) {
            $courselog->status    = 'done';
            $courselog->updatedat = date('Y-m-d H:i:s');
            $DB->update_record('block_mad2api_course_logs', $courselog, false);
        }

        self::send_original_course_logs($courseid);
        self::send_grades($courseid);

        mtrace("Envio de logs concluído para curso {$courseid}.\n");
    }

    /**
     * Sends the grades of the course students to the external API in batches.
     * @param int $courseid The ID of the course.
     * @return void
    */
    public static function send_grades($courseid) {
        global $DB, $USER;

        $courseid = (int)$courseid;

        mtrace("sending activities \n");

        $count = $DB->count_records('grade_items', [
            'courseid' => $courseid,
            'itemtype' => 'mod'
        ]);

        $perpage = 25;
        $endpage = (int)ceil($count / $perpage);
        $url = "api/v2/courses/{$courseid}/events";

        mtrace("Sending {$count} grade items for course {$courseid} in {$endpage} pages\n");

        for ($currentpage = 1; $currentpage <= $endpage; $currentpage++) {
            $offset = ($currentpage - 1) * $perpage;

            $gradeitems = $DB->get_records_sql("
                SELECT *
                  FROM {grade_items}
                 WHERE courseid = :courseid AND itemtype = 'mod'
            ", ['courseid' => $courseid], $offset, $perpage);

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

                $perpageitems = 25;
                $countitempage = $DB->count_records('grade_grades', ['itemid' => $item->id]);
                $enditempage = (int)ceil($countitempage / $perpageitems);

                for ($currentitempage = 1; $currentitempage <= $enditempage; $currentitempage++) {
                    $offsetitempage = ($currentitempage - 1) * $perpageitems;

                    $grades = $DB->get_records_sql("
                        SELECT *
                          FROM {grade_grades}
                         WHERE itemid = :itemid
                    ", ['itemid' => $item->id], $offsetitempage, $perpageitems);

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
     * @param int $courseid The ID of the course.
     * @return void
    */
    public static function send_original_course_logs($courseid) {
        global $DB, $USER;

        $courseid = (int)$courseid;

        mtrace("Sending original course logs for {$courseid}\n");

        $count = $DB->count_records_sql("
            SELECT COUNT(DISTINCT cm.id)
              FROM {course_modules} cm
              JOIN {modules} m ON cm.module = m.id
             WHERE cm.course = :courseid
        ", ['courseid' => $courseid]);

        $perpage = 25;
        $endpage = (int)ceil($count / $perpage);

        mtrace("Sending {$count} activities for course {$courseid} in {$endpage} pages\n");

        for ($currentpage = 1; $currentpage <= $endpage; $currentpage++) {
            $offset = ($currentpage - 1) * $perpage;
            $logs = [];

            mtrace("Sending page {$currentpage} for course {$courseid} \n");

            $coursemodules = $DB->get_records_sql("
                SELECT cm.id AS coursemoduleid,
                       m.name AS moduletype,
                       cm.instance,
                       cm.section,
                       cm.visible
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id
                 WHERE cm.course = :courseid
            ", ['courseid' => $courseid], $offset, $perpage);

            if (empty($coursemodules)) {
                mtrace("No course modules found for course {$courseid} \n");

                continue;
            }

            mtrace("Found " . count($coursemodules) . " course modules for course {$courseid} \n");

            foreach ($coursemodules as $coursemodule) {
                $tablename = $coursemodule->moduletype;
                $instanceid = (int)$coursemodule->instance;

                $instance = $DB->get_record($tablename, ['id' => $instanceid]);

                if (empty($instance) || !isset($instance->name)) {
                    mtrace("Instance not found for table {$tablename} with ID {$instanceid}\n");

                    continue;
                }

                $context = \context_module::instance($coursemodule->coursemoduleid, IGNORE_MISSING);

                if (empty($context) || !isset($context->instanceid)) {
                    mtrace("Context not found for activity #{$coursemodule->coursemoduleid} \n");

                    continue;
                }

                $cm = get_coursemodule_from_id(false, $coursemodule->coursemoduleid, 0, false, MUST_EXIST);
                $modname = $cm->modname;

                $tm = $DB->get_manager();

                if ($modname === 'hvp' && !$tm->table_exists('hvp')) {
                    $modname = 'h5pactivity';
                }

                $grades = grade_get_grades($cm->course, 'mod', $cm->modname, $cm->instance);
                $activityurl = new \moodle_url("/mod/{$cm->modname}/view.php", ['id' => $cm->id]);

                $logs[$context->id] = [
                    'id'                => 0,
                    'crud'              => 'c',
                    'other'             => json_encode([
                        'name'       => $instance->name,
                        'instanceid' => $context->instanceid,
                        'modulename' => $tablename,
                        'visible'    => $coursemodule->visible,
                        'gradable'   => !empty($grades->items),
                        'duedate'    => self::get_activity_duedate($cm),
                        'url'        => $activityurl->out()
                    ]),
                    'action'            => 'created',
                    'target'            => 'course_module',
                    'courseid'          => $courseid,
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

                mtrace("Sending activity {$instance->name} | {$instanceid} | visible? {$coursemodule->visible} \n");
            }

            $data = ['logs' => $logs];

            try {
                self::do_post_request("api/v2/courses/{$courseid}/logs/batch", $data, $courseid);
            } catch (\Exception $e) {
                mtrace("Error sending logs: " . $e->getMessage() . "\n");
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
            'assign'         => 'duedate',
            'quiz'           => 'timeclose',
            'lesson'         => 'deadline',
            'workshop'       => 'submissionend',
            'chat'           => 'chattime',
            'data'           => 'timeavailableto',
            'feedback'       => 'timeclose',
            'forum'          => 'duedate',
            'glossary'       => 'assesseduntil',
            'scorm'          => 'timeclose',
            'survey'         => null,
            'wiki'           => null,
            'h5pactivity'    => 'timeclose',
            'choice'         => 'timeclose',
            'database'       => 'timeavailableto',
            'assignoverride' => 'duedate',
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
     * @param int $courseid The ID of the course.
     * @return object An object containing the authorization URL or an empty object on failure.
     * @throws \moodle_exception If there is an error during the process.
    */
    public static function api_dashboard_auth_url($courseid) {
        global $USER;

        $courseid = (int)$courseid;
        $auth = [
            'teacherId' => $USER->id,
            'moodleId'  => $courseid,
            'email'     => $USER->email
        ];

        $resp = self::do_post_request('api/v2/authorize', $auth, $courseid);

        return $resp->data ?? (object)[];
    }

    /**
     * Checks if a user is enrolled in any monitored courses.
     *
     * @param int $userid The ID of the user to check.
     * @return object|null The course record if the user is enrolled in a monitored course, null otherwise.
    */
    public static function enrolled_monitored_courses($userid) {
        global $DB;

        $userid = (int)$userid;

        $monitoredcourses = $DB->get_records('block_mad2api_dashboard_settings', ['isenabled' => 1]);

        foreach ($monitoredcourses as $monitoredcourse) {
            $courseidrow = $DB->get_record_sql("
                SELECT c.id
                  FROM {course} c
                  JOIN {context} ct ON c.id = ct.instanceid
                  JOIN {role_assignments} ra ON ra.contextid = ct.id
                  JOIN {user} u ON u.id = ra.userid
                 WHERE c.id = :courseid AND u.id = :userid
            ", ['courseid' => (int)$monitoredcourse->courseid, 'userid' => $userid]);

            if (!empty($courseidrow)) {
                return $courseidrow;
            }
        }

        return null;
    }

    /**
     * Gets the total number of students enrolled in a course.
     * @param int $courseid The ID of the course.
     * @return int The total number of students enrolled in the course.
     */
    public static function get_course_students_count($courseid) {
        global $DB;

        $courseid    = (int)$courseid;
        $studentrole = (int)get_config('block_mad2api', 'studentrole');

        return $DB->count_records_sql("
            SELECT COUNT(*)
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
             WHERE c.id = :courseid AND r.id = :roleid
        ", ['courseid' => $courseid, 'roleid' => $studentrole]);
    }

    /**
     * Retrieves a paginated list of students enrolled in a course along with their details.
     *
     * @param int $courseid The ID of the course.
     * @param int $perpage The number of students to retrieve per page.
     * @param int $offset The offset for pagination.
     * @return array An array of student records with their details.
    */
    public static function get_course_students($courseid, $perpage, $offset) {
        global $DB;

        $courseid    = (int)$courseid;
        $perpage     = (int)$perpage;
        $offset      = (int)$offset;
        $studentrole = (int)get_config('block_mad2api', 'studentrole');

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
            'courseid_gi' => $courseid,
            'courseid'    => $courseid,
            'roleid'      => $studentrole
        ];

        $students = $DB->get_records_sql($sql, $params, $offset, $perpage);

        return self::camelizeArray($students);
    }

    /**
     * Retrieves details of a specific student enrolled in a course.
     *
     * @param int $courseid The ID of the course.
     * @param int $studentid The ID of the student.
     * @return object|null An object containing the student's details or null if not found.
    */
    public static function get_course_student($courseid, $studentid) {
        global $DB;

        $courseid    = (int)$courseid;
        $studentid   = (int)$studentid;
        $studentrole = (int)get_config('block_mad2api', 'studentrole');

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
            'courseid_gi' => $courseid,
            'courseid'    => $courseid,
            'roleid'      => $studentrole,
            'userid'      => $studentid,
        ];

        $student = $DB->get_record_sql($sql, $params);

        return self::camelizeObject($student);
    }

    /**
     * Retrieves details of a specific user in the context of a course, including their role.
     *
     * @param int $userid The ID of the user.
     * @param int $courseid The ID of the course.
     * @return object|null An object containing the user's details and role or null if not found.
    */
    public static function get_user($userid, $courseid) {
        global $DB;

        $userid      = (int)$userid;
        $courseid    = (int)$courseid;
        $studentrole = (int)get_config('block_mad2api', 'studentrole');

        $coordinatorrolescfg = (string)get_config('block_mad2api', 'adminroles');
        $coordinatorroles = self::parse_role_ids_list($coordinatorrolescfg);
        list($insql, $inparams) = $DB->get_in_or_equal($coordinatorroles, SQL_PARAMS_NAMED, 'cr');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   (CASE WHEN u.lastaccess = '0' THEN 'false' ELSE 'true' END) AS logged_in,
                   AVG(g.rawgrade) AS current_grade, u.phone1, u.phone2,
                   r.shortname AS moodle_role,
                   (CASE
                        WHEN r.id = :studentrole THEN 'student'
                        WHEN r.id {$insql} THEN 'coordinator'
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
            'studentrole' => $studentrole,
            'courseid_gi' => $courseid,
            'courseid'    => $courseid,
            'userid'      => $userid,
        ], $inparams);

        $user = $DB->get_record_sql($sql, $params);

        return self::camelizeObject($user);
    }

    /**
     * Retrieves a list of teachers assigned to a specific course.
     * @param int $courseid The ID of the course.
     * @return array An array of teacher records with their details.
    */
    public static function get_course_teachers($courseid) {
        global $DB;

        $courseid = (int)$courseid;

        $rolescfg = (string)get_config('block_mad2api', 'roles');
        $rolesid  = self::parse_role_ids_list($rolescfg);
        if (empty($rolesid)) { return []; }

        list($insql, $inparams) = $DB->get_in_or_equal($rolesid, SQL_PARAMS_NAMED, 'tr');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   r.shortname AS moodle_role, u.phone1, u.phone2
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
             WHERE c.id = :courseid AND r.id {$insql}
        ";

        $params = array_merge(['courseid' => $courseid], $inparams);

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Retrieves a list of coordinators assigned to a specific course.
     * @param int $courseid The ID of the course.
     * @return array An array of coordinator records with their details.
    */
    public static function get_course_coordinators($courseid) {
        global $DB;

        $courseid = (int)$courseid;

        $rolescfg = (string)get_config('block_mad2api', 'admin_roles');
        $rolesid  = self::parse_role_ids_list($rolescfg);
        if (empty($rolesid)) { return []; }

        list($insql, $inparams) = $DB->get_in_or_equal($rolesid, SQL_PARAMS_NAMED, 'cr');

        $sql = "
            SELECT u.id AS user_id, u.email,
                   u.firstname AS first_name, u.lastname AS last_name,
                   r.shortname AS moodle_role, u.phone1, u.phone2
              FROM {course} c
              JOIN {context} ct ON c.id = ct.instanceid
              JOIN {role_assignments} ra ON ra.contextid = ct.id
              JOIN {user} u ON u.id = ra.userid
              JOIN {role} r ON r.id = ra.roleid
             WHERE c.id = :courseid AND r.id {$insql}
        ";

        $params = array_merge(['courseid' => $courseid], $inparams);

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Converts the keys of an object from snake_case to camelCase.
     * @param object|array $obj The object or associative array to convert.
     * @return array The new associative array with camelCase keys.
    */
    public static function camelizeObject($obj) {
        $newobj = [];

        if (gettype($obj) === 'boolean' || $obj === null) {
            return $newobj;
        }

        foreach ($obj as $key => $value) {
            $newobj[self::convertToCamel($key, '_')] = $value;
        }

        return $newobj;
    }

    /**
     * Converts an array of objects from snake_case to camelCase keys.
     * @param array $array The array of objects to convert.
     * @return array The new array with camelCase keys.
    */
    public static function camelizeArray($array) {
        $formattedarray = [];

        foreach ($array as $item) {
            $formattedarray[] = self::camelizeObject($item);
        }

        return $formattedarray;
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
     * @param int $courseid The ID of the course to potentially disable.
     * @return void
    */
    private static function disable_course_if_not_found($ch, $courseid) {
        global $DB;

        $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (in_array((int)$httpstatus, [400, 404], true)) {
            $resources = $DB->get_records(
                'block_mad2api_dashboard_settings', ['courseid' => (int)$courseid, 'isenabled' => 1]
            );

            foreach ($resources as $resource) {
                $data = [
                    'id'         => $resource->id,
                    'isenabled'  => 0
                ];

                $DB->update_record('block_mad2api_dashboard_settings', $data, false);
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

    public static function is_course_enabled($courseid) {
        global $DB;

        return (bool)$DB->get_record('block_mad2api_dashboard_settings', [
            'courseid'  => (int)$courseid,
            'isenabled' => 1
        ]);
    }

    /**
     * Sends a POST request to the specified URL with the given body and handles course disabling if necessary.
     * @param string $url The endpoint URL (relative to the base API URL).
     * @param array|object $body The data to send in the POST request.
     * @param int|null $courseid The ID of the course (optional, used for disabling if not found).
     * @return object|null The response data from the API or null on failure.
    */
    public static function do_post_request($url, $body, $courseid = null) {
        $apikey = get_config('block_mad2api', 'apikey');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            'accept: application/json',
            'Content-Type: application/json',
            "API-KEY: {$apikey}"
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($courseid !== null) {
            self::disable_course_if_not_found($ch, (int)$courseid);
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
        $apikey = get_config('block_mad2api', 'apikey');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            'accept: application/json',
            'Content-Type: application/json',
            "API-KEY: {$apikey}"
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
        $apikey = get_config('block_mad2api', 'apikey');
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::get_url_for($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            'accept: application/json',
            'Content-Type: application/json',
            "API-KEY: {$apikey}"
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
        $apiurl = rtrim((string)get_config('block_mad2api', 'apiurl'), '/');
        $path   = ltrim($path, '/');

        return "{$apiurl}/{$path}";
    }
}
