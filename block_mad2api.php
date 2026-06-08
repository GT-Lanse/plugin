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
 * Renders buttons to access the dashboard, enable/disable course monitoring,
 * and trigger data loading, with role checking and AJAX integrations.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/mad_dashboard.php');

class block_mad2api extends block_base {

    /**
     * Define the block title.
     *
     * @return void
    */
    public function init() {
        $this->title = get_string('pluginname', 'block_mad2api');
    }

    /**
     * Define which formats this block can appear in.
     * Adjusted according to your original code: site and course pages; not in mod/quiz.
     *
     * @return array
    */
    public function applicable_formats() {
        return array(
            'site-index'        => true,
            'course-view'       => true,
            'course-view-social'=> false,
            'mod'               => true,
            'mod-quiz'          => false,
            'my'                => false,
        );
    }

    /**
     * Allow multiple instances of this block on the same page.
     *
     * @return bool
    */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Declares that this plugin has global (admin) settings.
     *
     * @return bool
    */
    public function has_config() {
        return true;
    }

    /**
     * Disable course monitoring when the last LANSE block is removed from a course.
     *
     * @return bool
     */
    public function instance_delete() {
        $courseid = $this->get_courseid_from_instance();

        if (empty($courseid) || $this->course_has_other_instances((int)$courseid)) {
            return true;
        }

        $this->disable_monitoring_for_deleted_instance((int)$courseid);

        return true;
    }

    /**
     * Resolve the course ID related to the current block instance.
     *
     * @return int|null
     */
    private function get_courseid_from_instance() {
        global $DB;

        if (empty($this->instance) || empty($this->instance->parentcontextid)) {
            return null;
        }

        $context = context::instance_by_id((int)$this->instance->parentcontextid, IGNORE_MISSING);

        if (!$context) {
            return null;
        }

        if ((int)$context->contextlevel === CONTEXT_COURSE) {
            return (int)$context->instanceid;
        }

        if ((int)$context->contextlevel === CONTEXT_MODULE) {
            $coursemodule = $DB->get_record(
                'course_modules',
                ['id' => (int)$context->instanceid],
                'course',
                IGNORE_MISSING
            );

            return $coursemodule ? (int)$coursemodule->course : null;
        }

        return null;
    }

    /**
     * Check whether another LANSE block instance still exists in the course.
     *
     * @param int $courseid
     * @return bool
     */
    private function course_has_other_instances($courseid) {
        global $DB;

        $instanceid = !empty($this->instance->id) ? (int)$this->instance->id : 0;

        $sql = "
            SELECT COUNT(1)
              FROM {block_instances} bi
              JOIN {context} ctx ON ctx.id = bi.parentcontextid
         LEFT JOIN {course_modules} cm
                ON cm.id = ctx.instanceid AND ctx.contextlevel = :modulecontextjoin
             WHERE bi.blockname = :blockname
               AND bi.id <> :instanceid
               AND (
                    (ctx.contextlevel = :coursecontext AND ctx.instanceid = :courseid)
                    OR (ctx.contextlevel = :modulecontextwhere AND cm.course = :modulecourseid)
               )
        ";

        return $DB->count_records_sql($sql, [
            'modulecontextjoin' => CONTEXT_MODULE,
            'blockname' => 'mad2api',
            'instanceid' => $instanceid,
            'coursecontext' => CONTEXT_COURSE,
            'courseid' => (int)$courseid,
            'modulecontextwhere' => CONTEXT_MODULE,
            'modulecourseid' => (int)$courseid,
        ]) > 0;
    }

    /**
     * Disable local course monitoring and register the Moodle log event.
     *
     * @param int $courseid
     * @return void
     */
    private function disable_monitoring_for_deleted_instance($courseid) {
        global $DB;

        $dashboardsetting = $DB->get_record('block_mad2api_dash_settings', [
            'courseid' => (int)$courseid,
            'isenabled' => 1,
        ]);

        if (empty($dashboardsetting->id)) {
            return;
        }

        $updated = $DB->update_record('block_mad2api_dash_settings', [
            'id' => $dashboardsetting->id,
            'courseid' => (int)$courseid,
            'updatedat' => date('Y-m-d H:i:s'),
            'isenabled' => 0,
        ]);

        if (!$updated) {
            return;
        }

        $context = context_course::instance((int)$courseid, IGNORE_MISSING);

        if (!$context) {
            return;
        }

        \block_mad2api\event\monitoring_disabled::create([
            'context' => $context,
            'courseid' => (int)$courseid,
            'objectid' => (int)$dashboardsetting->id,
        ])->trigger();
    }

    /**
     * Builds the block content.
     *
     * @return stdClass|null
    */
    public function get_content() {
        global $CFG, $OUTPUT, $PAGE, $COURSE, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content            = new stdClass();
        $this->content->text      = '';
        $this->content->footer    = '';

        if (empty($COURSE) || empty($COURSE->id)) {
            return $this->content;
        }

        $context = context_course::instance($COURSE->id);

        if (!\block_mad2api\mad_dashboard::current_user_can_view_dashboard((int)$COURSE->id, (int)$USER->id)) {
            return $this->content;
        }

        $appurl = get_config('block_mad2api', 'appurl');

        $ltiurl = new moodle_url('/blocks/mad2api/view.php', array(
            'courseid'   => $COURSE->id,
            'coursename' => format_string($COURSE->fullname)
        ));

        $enabled = $DB->get_record('block_mad2api_dash_settings', array(
            'courseid'  => $COURSE->id,
            'isenabled' => 1
        ));

        if ($enabled) {
            \block_mad2api\mad_dashboard::check_data_on_api($COURSE->id);
            \block_mad2api\mad_dashboard::enable_course($COURSE->id);
        }

        $actions = array();

        $actions[] = html_writer::link(
            $ltiurl,
            get_string('access_dashboard', 'block_mad2api'),
            array(
                'class' => 'btn btr-primary',
                'id'    => 'lti-lanse',
                'class' => 'plugin-link btn' . (!$enabled ? ' disabled' : ''),
                'style' => 'width:100%;margin-top:10px;background-color:#04626a;color:#fff;'
            )
        );

        $actions[] = html_writer::tag(
            'a',
            get_string('open_dashboard', 'block_mad2api'),
            array(
                'id'    => 'access-dashboard',
                'href'  => $appurl,
                'class' => 'plugin-link btn',
                'class' => 'plugin-link btn' . (!$enabled ? ' disabled' : ''),
                'style' => 'width:100%;margin:10px 0;color:#04626a;border:3px solid #04626a;',
                'target' => '_blank'
            )
        );

        $actions[] = html_writer::tag(
            'a',
            get_string('enable_dashboard', 'block_mad2api'),
            array(
                'id'    => 'enable-settings',
                'href'  => '#',
                'class' => 'plugin-link btn' . ($enabled ? ' disabled' : ''),
                'style' => 'width:100%;margin:10px 0;background-color:#04626a;color:#fff;'
            )
        );

        $actions[] = html_writer::tag(
            'a',
            get_string('disable_dashboard', 'block_mad2api'),
            array(
                'id'    => 'disable-settings',
                'href'  => '#',
                'class' => 'plugin-link btn' . ($enabled ? '' : ' disabled'),
                'style' => 'width:100%;margin:10px 0;color:#b00000;background-color:#fff;'
            )
        );

        $this->content->text = html_writer::div(implode('', $actions), 'plugin-link-container');

        $PAGE->requires->js_call_amd(
            'block_mad2api/enable_button_api_call', 'init',
            array((int)$COURSE->id, (int)$CFG->version)
        );

        return $this->content;
    }
}
