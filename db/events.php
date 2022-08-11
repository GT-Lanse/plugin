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
 * Event observer.
 *
 * @package   block_mad2api
 * @category  event
 * @copyright 2022 Eduardo de Vila <eduardodevila1@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
  array(
    'eventname' => '\core\event\user_enrolment_created',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\core\event\user_enrolment_deleted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\core\event\user_enrolment_updated',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  )
  array(
    'eventname' => '\mod_book\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_chat\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_chat\event\sessions_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_choice\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_choice\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_choice\event\report_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_data\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_data\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_data\event\template_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_feedback\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_feedback\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_folder\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_folder\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_forum\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_forum\event\discussion_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_forum\event\forum_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_lesson\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_lesson\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_lesson\event\essay_attempt_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_lesson\event\highscores_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_lti\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_lti\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_page\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_page\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_resource\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_resource\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_scorm\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_scorm\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_scorm\event\interactions_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_scorm\event\report_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_scorm\event\tracks_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_scorm\event\user_report_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_url\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_url\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\comments_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\course_module_instance_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\page_diff_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\page_history_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\page_map_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\page_version_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\page_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_workshop\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_workshop\event\instances_list_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_workshop\event\submission_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => 'report_log\event\content_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => 'report_loglive\event\content_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => 'report_outline\event\content_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => 'report_participation\event\content_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
  array(
    'eventname' => 'report_stats\event\content_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false, // This means that we get events only after transaction commit.
    'priority'  => 1000,
  ),
);
