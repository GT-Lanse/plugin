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
 * Event triggered when the LANSE dashboard is viewed.
 *
 * @package   block_mad2api
 * @copyright 2025
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_viewed extends \core\event\base {

    /**
     * Initialize event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns the localized event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdashboardviewed', 'block_mad2api');
    }

    /**
     * Returns the event description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the LANSE dashboard for the course with id '{$this->courseid}'.";
    }

    /**
     * Returns the dashboard URL related to this event.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/mad2api/view.php', ['courseid' => $this->courseid]);
    }

    /**
     * Returns mapping information for extra event data.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
