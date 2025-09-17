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
        $items->add_database_table('mad2api_course_logs', [
            'courseid'   => 'privacy:metadata:course_logs:courseid',
            'userid'     => 'privacy:metadata:course_logs:userid',
            'action'     => 'privacy:metadata:course_logs:action',
            'payload'    => 'privacy:metadata:course_logs:payload',
            'status'     => 'privacy:metadata:course_logs:status',
            'createdat'  => 'privacy:metadata:course_logs:createdat',
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

    /**
     * Returns list of contexts containing user data.
     *
     * We assume data is per course (context_course) in mad2api_course_logs.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {mad2api_course_logs} l ON l.courseid = c.id
                 WHERE l.userid = :userid";
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid'       => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $courseid = $context->instanceid;

            $logs = $DB->get_records('mad2api_course_logs', [
                'courseid' => $courseid,
                'userid'   => $userid,
            ], 'id ASC');

            if (!$logs) {
                continue;
            }

            $export = array_map(function($r) {
                return (object)[
                    'id'        => (int)$r->id,
                    'courseid'  => (int)$r->courseid,
                    'userid'    => (int)$r->userid,
                    'action'    => $r->action,
                    'payload'   => $r->payload,
                    'status'    => $r->status,
                    'createdat' => $r->createdat,
                ];
            }, array_values($logs));

            writer::with_context($context)->export_data(
                // Path/area do export no JSON do usuário.
                [get_string('privacy:export:path', 'block_mad2api')],
                (object)['course_logs' => $export]
            );
        }
    }

    /**
     * Delete data for a single user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $DB->delete_records('mad2api_course_logs', [
                'courseid' => $context->instanceid,
                'userid'   => $userid,
            ]);
        }
    }

    /**
     * Populate a userlist with users who have data in the given context.
     *
     * Required when implementing bulk deletion.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT DISTINCT l.userid
                  FROM {mad2api_course_logs} l
                  JOIN {course} c ON c.id = l.courseid
                 WHERE c.id = :courseid";
        $params = ['courseid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Bulk-delete data for all users in the given approved_userlist (single context).
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['courseid' => $context->instanceid], $inparams);

        $DB->delete_records_select(
            'mad2api_course_logs',
            "courseid = :courseid AND userid $insql",
            $params
        );
    }

    /**
     * Delete ALL user data in the given context (e.g., course reset).
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records('mad2api_course_logs', ['courseid' => $context->instanceid]);
    }
}
