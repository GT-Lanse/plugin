<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_mad2api\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy API provider for block_mad2api.
 *
 * @package   block_mad2api
 * @copyright LANSE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns metadata about personal data stored locally and sent to external systems.
     * @param collection $items The collection of metadata items.
     * @return collection The populated metadata collection.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_external_location_link('mad2api_external_service', [
            'userid'     => 'privacy:metadata:external:userid',
            'courseid'   => 'privacy:metadata:external:courseid',
            'fullname'   => 'privacy:metadata:external:fullname',
            'email'      => 'privacy:metadata:external:email',
            'enrolments' => 'privacy:metadata:external:enrolments',
            'grades'     => 'privacy:metadata:external:grades',
            'progress'   => 'privacy:metadata:external:progress',
            'lastaccess' => 'privacy:metadata:external:lastaccess',
        ], 'privacy:metadata:external');

        return $items;
    }

    /**
     * Returns a list of contexts where the specified user has personal data.
     * @param int $userid The ID of the user.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Adds users to the userlist who have data in the specified context.
     * @param userlist $userlist The userlist to populate.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        return;
    }

    /**
     * Exports all user data for the specified contexts.
     * @param approved_contextlist $contextlist The list of approved contexts for export.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        return;
    }

    /**
     * Deletes all data for all users in the specified context.
     * @param \context $context The context to delete data from.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        return;
    }

    /**
     * Deletes personal data for the specified user across multiple contexts.
     * @param approved_contextlist $contextlist The list of approved contexts to delete from.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        return;
    }

    /**
     * Deletes data for a list of users within a single context.
     * @param approved_userlist $userlist The list of approved users to delete.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        return;
    }
}
