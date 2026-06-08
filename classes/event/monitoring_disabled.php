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

namespace block_mad2api\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when LANSE course monitoring is disabled.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class monitoring_disabled extends \core\event\base {

    /**
     * Initialize event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'block_mad2api_dash_settings';
    }

    /**
     * Returns the localized event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventmonitoringdisabled', 'block_mad2api');
    }

    /**
     * Returns the event description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' disabled LANSE monitoring for the course with id '{$this->courseid}'.";
    }

    /**
     * Returns the course URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
    }

    /**
     * Returns object ID mapping information.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'block_mad2api_dash_settings', 'restore' => 'block_mad2api_dash_settings'];
    }
}
