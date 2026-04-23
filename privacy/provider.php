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
 * Privacy API provider for block_mad2api.
 *
 * @package   block_mad2api
 * @copyright LANSE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mad2api\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Implements Moodle Privacy API for block_mad2api.
 *
 * We (a) declare stored personal data (local DB),
 * (b) declare exported personal data to an external system,
 * (c) implement discover/export/delete for per-user data.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about personal data stored locally, and external recipients.
     *
     * @param collection $items
     * @return collection
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('block_mad2api_course_logs', [
            'courseid'   => 'privacy:metadata:course_logs:courseid',
            'status'     => 'privacy:metadata:course_logs:status',
            'studentssent'    => 'privacy:metadata:course_logs:studentssent',
            'lastlogpage'    => 'privacy:metadata:course_logs:lastlogpage',
            'createdat'  => 'privacy:metadata:course_logs:createdat',
            'updatedat'  => 'privacy:metadata:course_logs:updatedat',
        ], 'privacy:metadata:course_logs');

        $items->add_external_location_link('mad2api_external_service', [
            'userid'      => 'privacy:metadata:external:userid',
            'courseid'    => 'privacy:metadata:external:courseid',
            'fullname'    => 'privacy:metadata:external:fullname',
            'email'       => 'privacy:metadata:external:email',
            'enrolments'  => 'privacy:metadata:external:enrolments',
            'grades'      => 'privacy:metadata:external:grades',
            'progress'    => 'privacy:metadata:external:progress',
            'lastaccess'  => 'privacy:metadata:external:lastaccess',
        ], 'privacy:metadata:external');

        return $items;
    }
}
