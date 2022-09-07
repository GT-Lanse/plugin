<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Newblock block caps.
 *
 * @package    block_mad2api
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('classes/mad_dashboard.php');

class block_mad2api extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_mad2api');
    }

    function get_content() {
        global $CFG, $OUTPUT, $PAGE, $COURSE, $USER, $DB;

        $context = context_course::instance($COURSE->id);

        if (!\block_mad2api\mad_dashboard::is_current_user_course_teacher($context->id)) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $enabled = $DB->get_record(
            "mad2api_dashboard_settings",
            array( 'user_id' => $USER->id, 'course_id' => $COURSE->id, 'is_enabled' => 1)
        );

        $PAGE->requires->js_call_amd('block_mad2api/enable_button_api_call', 'init', array($COURSE->id));

        if ($enabled) {
            $course_info = \block_mad2api\mad_dashboard::enable($COURSE->id);
            $url = $course_info[0]['url'];

            $this->content->text =
                '<div class="plugin-link-container">
                    <div>
                        <a id="access-dashboard" class="access-dashboard-button" href="'. $url .'" target="_blank">Acessar Dashboard</a>
                    </div>
                    <a id="enable-settings" class="plugin-link disabled" href="">Habilitar Dashboard</a>
                    <a id="disable-settings" class="plugin-link" href="">Desabilitar Dashboard</a>
                </div>';
        } else {
            $this->content->text =
                '<div class="plugin-link-container">
                    <div>
                        <a id="access-dashboard" class="access-dashboard-button disabled" href="" target="_blank">Acessar Dashboard</a>
                    </div>
                    <a id="enable-settings" class="plugin-link" href="">Habilitar Dashboard</a>
                    <a id="disable-settings" class="plugin-link disabled" href="">Desabilitar Dashboard</a>
                </div>';
        }

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('all' => false,
                     'site' => true,
                     'site-index' => true,
                     'course-view' => true,
                     'course-view-social' => false,
                     'mod' => true,
                     'mod-quiz' => false);
    }

    public function instance_allow_multiple() {
          return true;
    }

    function has_config() {return true;}
}
