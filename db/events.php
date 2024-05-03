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

$observers = array(
  array(
    'eventname' => '\core\event\grade_item_created',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  array(
    'eventname' => '\core\event\user_enrolment_created',
    'callback'  => 'block_mad2api_observer::new_user_enrolment_created',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\core\event\user_enrolment_deleted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  array(
    'eventname' => '\core\event\course_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  array(
    'eventname' => '\core\event\course_module_created',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\core\event\course_module_updated',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\core\event\course_module_deleted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  array(
    'eventname' => '\core\event\user_graded',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // quiz
  array(
    'eventname' => '\mod_quiz\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_quiz\event\attempt_submitted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // h5p
  array(
    'eventname' => '\mod_h5pactivity\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_h5pactivity\event\statement_received',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // assign
  array(
    'eventname' => '\mod_assign\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_assign\event\assessable_submitted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // forum
  array(
    'eventname' => '\mod_forum\event\post_created',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_forum\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // choice
  array(
    'eventname' => '\mod_choice\event\answer_created',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_choice\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // wiki
  array(
    'eventname' => '\mod_wiki\event\comment_created',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_wiki\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // url
  array(
    'eventname' => '\mod_url\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // file
  array(
    'eventname' => '\mod_resource\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // page
  array(
    'eventname' => '\mod_page\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // feedback
  array(
    'eventname' => '\mod_feedback\event\response_submitted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_feedback\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // workshop
  array(
    'eventname' => '\mod_workshop\event\assessable_uploaded',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_workshop\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // chat
  array(
    'eventname' => '\mod_chat\event\message_sent',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_chat\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // survey
  array(
    'eventname' => '\mod_survey\event\response_submitted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_survey\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // scorm
  array(
    'eventname' => '\mod_scorm\event\scoreraw_submitted',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_scorm\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),

  // lesson
  array(
    'eventname' => '\mod_lesson\event\question_answered',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  ),
  array(
    'eventname' => '\mod_lesson\event\course_module_viewed',
    'callback'  => 'block_mad2api_observer::new_event',
    'internal'  => false,
    'priority'  => 1000,
  )
);
