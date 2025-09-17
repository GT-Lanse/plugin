<?php
/**
 * Block: LANSE Dashboard (block_mad2api)
 *
 * Renders buttons to access the dashboard, enable/disable course monitoring,
 * and trigger data loading, with role checking and AJAX integrations.
 *
 * @package   block_mad2api
 * @copyright ...
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

        $isteacher = \block_mad2api\mad_dashboard::is_current_user_course_teacher($context->id);
        $iscoord   = \block_mad2api\mad_dashboard::is_current_user_course_coordinator($context->id);

        if (!$isteacher && !$iscoord) {
            $this->content->text = html_writer::div(
                get_string('not_teacher', 'block_mad2api'),
                'alert alert-info'
            );

            return $this->content;
        }

        $appUrl = get_config('mad2api', 'app_url');

        $lti_url = new moodle_url('/blocks/mad2api/view.php', array(
            'courseid'   => $COURSE->id,
            'coursename' => format_string($COURSE->fullname)
        ));

        $enabled = $DB->get_record('mad2api_dashboard_settings', array(
            'course_id'  => $COURSE->id,
            'is_enabled' => 1
        ));

        if ($enabled) {
            \block_mad2api\mad_dashboard::check_data_on_api($COURSE->id);
            \block_mad2api\mad_dashboard::enable($COURSE->id);
        }

        $openbtn = html_writer::link(
            $lti_url,
            get_string('access_dashboard', 'block_mad2api'),
            array(
                'class' => 'btn btr-primary',
                'id'    => 'lti-lanse',
                'style' => 'width:100%;margin-top:10px;background-color:#04626a;color:#fff;'
            )
        );

        $actions = array();

        $actions[] = html_writer::tag(
            'a',
            get_string('open_dashboard', 'block_mad2api'),
            array(
                'id'    => 'access-dashboard',
                'href'  => $appUrl,
                'class' => 'plugin-link btn',
                'style' => 'width:100%;margin:10px 0;color:#04626a;border:3px solid #04626a;'
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

        $this->content->text = $openbtn .
            html_writer::div(implode('', $actions), 'plugin-link-container');

        $PAGE->requires->js_call_amd(
            'block_mad2api/enable_button_api_call', 'init',
            array((int)$COURSE->id, (int)$CFG->version)
        );

        return $this->content;
    }
}
