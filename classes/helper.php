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
 * Links and settings
 *
 * Class containing a set of helpers, based on admin\tool\uploadcourse by 2013 Frédéric Massart.
 *
 * @package    tool_uploadpageresults
 * @copyright  2019-2020 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Class containing a set of helpers.
 *
 * @package   tool_uploadpageresults
 * @copyright 2019-2020 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uploadpageresults_helper {
    /**
     * Validate we have the minimum info to create/update course result
     *
     * @param object $record The record we imported
     * @return bool true if validated
     */
    public static function validate_import_record($record) {
        // As a minimum we need.
        // course idnumber.
        // user username.
        if (empty($record->course_idnumber)) {
            return false;
        }

        if (empty($record->user_username)) {
            return false;
        }
        return true;
    }

    /**
     * Retrieve a page by its id.
     *
     * @param int $pageid page identifier
     * @return object page.
     */
    public static function get_page_by_id($pageid) {
        global $DB;

        $params = array('id' => $pageid);
        if ($page = $DB->get_record('page', $params)) {
            return $page;
        } else {
             return null;
        }
    }

    /**
     * Retrieve a course by its idnumber.
     *
     * @param string $courseidnumber course idnumber
     * @return object course or null
     */
    public static function get_course_by_idnumber($courseidnumber) {
        global $DB;

        $params = array('idnumber' => $courseidnumber);
        $courses = $DB->get_records('course', $params);

        if (count($courses) == 1) {
            $course = array_pop($courses);
            return $course;
        } else {
            return null;
        }
    }

    /**
     * Retrieve course module $cm by course idnumber.
     *
     * use modinfolib.php
     *
     * @param string $course course object
     * @return stdClass $cm Activity or null if none found
     */
    public static function get_coursemodule_from_course_idnumber($course) {
        $cm = null;
        foreach (get_fast_modinfo($course->id, -1)->get_instances_of('page') as $pages => $cminfo) {
            if ($cminfo->idnumber == $course->idnumber) {
                $cm = $cminfo->get_course_module_record();
                break;
            }
        }
        return $cm;
    }

    /**
     * Marks the Page viewed.
     *
     * @param object $course
     * @param object $cm Course-module
     * @param object $context Set to null to get context_module (default)
     * @param int $userid Set to 0 for current user (default)
     * @return bool True if succesful, false if not.
     */
    public static function page_viewed($course, $cm, $context = null, $userid = 0) {
        global $DB, $USER;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        if (empty($context)) {
            $context = context_module::instance($cm->id);
        }

        // Get page details.
        $page = $DB->get_record('page', array('id' => $cm->instance), '*', MUST_EXIST);

        // Trigger course_module_viewed event.
        $params = array(
            'context' => $context,
            'objectid' => $page->id,
            'userid' => $userid,
        );

        $event = \mod_page\event\course_module_viewed::create($params);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('page', $page);
        $event->trigger();

        // Completion.
        $completion = new completion_info($course);
        $completion->set_module_viewed($cm, $userid);

        return true;
    }

    /**
     * Update page activity viewed
     *
     * This will show a developer debug warning when run in Moodle UI because
     * of the function set_module_viewed in completionlib.php details copied below:
     *
     * Note that this function must be called before you print the page header because
     * it is possible that the navigation block may depend on it. If you call it after
     * printing the header, it shows a developer debug warning.
     *
     * @param object $record Validated Imported Record
     * @return object $response contains details of processing
     */
    public static function mark_page_as_completed($record) {
        global $DB;

        $response = new \stdClass();
        $response->added = 0;
        $response->skipped = 0;
        $response->error = 0;
        $response->message = null;

        // Student role to use when enroling user.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Get the course by the idnumber.
        if ($course = self::get_course_by_idnumber($record->course_idnumber)) {
            $response->course = $course;

            if ($user = $DB->get_record('user', array('username' => $record->user_username), 'id,username')) {
                $response->user = $user;

                if ($cm = self::get_coursemodule_from_course_idnumber($course)) {
                    $message = '';
                    // Execute real Moodle enrolment for user.
                    enrol_try_internal_enrol($course->id, $user->id, $studentrole->id);

                    // Completion.
                    $completion = new completion_info($course);
                    // Get the current state for the activity and user.
                    $currentstate = $completion->get_data($cm, false, $user->id, null);

                    if ($currentstate->viewed != COMPLETION_VIEWED) {
                        self::page_viewed($course, $cm, null, $user->id);
                        $response->message = 'Page - Completion Viewed set to true.';
                        $response->skipped = 0;
                        $response->added = 1;
                    } else {
                        $response->message = 'Page completion viewed already exists.';
                        $response->skipped = 1;
                        $response->added = 0;
                    }
                } else {
                    $response->message = 'Page with idnumber '.$record->course_idnumber.' does not exist';
                    $response->skipped = 1;
                    $response->added = 0;
                }
            } else {
                $response->skipped = 1;
                $response->added = 0;
                $response->message = 'User with username '.$record->user_username.' does not exist';
            }
        } else {
            // Course does not exist so skip.
            $response->message = 'Course with idnumber '.$record->course_idnumber.' does not exist';
            $response->skipped = 1;
            $response->added = 0;
        }

        return $response;
    }
}