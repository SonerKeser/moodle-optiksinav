<?php
// This file is part of the OptikSınav activity module for Moodle.
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

/**
 * Privacy Subsystem implementation for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_optiksinav\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\transform;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider: the plugin stores per-user exam attempts and answers.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the user data stored by this plugin.
     *
     * @param collection $collection The metadata collection to add to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('optiksinav_attempt', [
            'userid' => 'privacy:metadata:optiksinav_attempt:userid',
            'attemptnumber' => 'privacy:metadata:optiksinav_attempt:attemptnumber',
            'state' => 'privacy:metadata:optiksinav_attempt:state',
            'timestart' => 'privacy:metadata:optiksinav_attempt:timestart',
            'timefinish' => 'privacy:metadata:optiksinav_attempt:timefinish',
            'correctcount' => 'privacy:metadata:optiksinav_attempt:correctcount',
            'wrongcount' => 'privacy:metadata:optiksinav_attempt:wrongcount',
            'blankcount' => 'privacy:metadata:optiksinav_attempt:blankcount',
            'net' => 'privacy:metadata:optiksinav_attempt:net',
            'grade' => 'privacy:metadata:optiksinav_attempt:grade',
        ], 'privacy:metadata:optiksinav_attempt');

        $collection->add_database_table('optiksinav_attempt_answer', [
            'slot' => 'privacy:metadata:optiksinav_attempt_answer:slot',
            'answer' => 'privacy:metadata:optiksinav_attempt_answer:answer',
        ], 'privacy:metadata:optiksinav_attempt_answer');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user data for the specified user.
     *
     * @param int $userid The user id.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :modlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {optiksinav} o ON o.id = cm.instance
                  JOIN {optiksinav_attempt} a ON a.optiksinavid = o.id
                 WHERE a.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'modlevel' => CONTEXT_MODULE,
            'modname' => 'optiksinav',
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT a.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {optiksinav} o ON o.id = cm.instance
                  JOIN {optiksinav_attempt} a ON a.optiksinavid = o.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, [
            'modname' => 'optiksinav',
            'cmid' => $context->instanceid,
        ]);
    }

    /**
     * Export all user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export data for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('optiksinav', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $attempts = $DB->get_records(
                'optiksinav_attempt',
                ['optiksinavid' => $cm->instance, 'userid' => $user->id],
                'attemptnumber ASC'
            );
            if (!$attempts) {
                continue;
            }

            $data = (object)['attempts' => []];
            foreach ($attempts as $attempt) {
                $answers = $DB->get_records(
                    'optiksinav_attempt_answer',
                    ['attemptid' => $attempt->id],
                    'slot ASC',
                    'slot, answer'
                );
                $data->attempts[] = [
                    'attemptnumber' => $attempt->attemptnumber,
                    'state' => $attempt->state,
                    'timestart' => $attempt->timestart ? transform::datetime($attempt->timestart) : null,
                    'timefinish' => $attempt->timefinish ? transform::datetime($attempt->timefinish) : null,
                    'correctcount' => $attempt->correctcount,
                    'wrongcount' => $attempt->wrongcount,
                    'blankcount' => $attempt->blankcount,
                    'net' => $attempt->net,
                    'grade' => $attempt->grade,
                    'answers' => array_map(function ($a) {
                        return ['slot' => $a->slot, 'answer' => $a->answer];
                    }, array_values($answers)),
                ];
            }

            $contextdata = helper::get_context_data($context, $user);
            writer::with_context($context)->export_data([], (object)array_merge((array)$contextdata, (array)$data));
        }
    }

    /**
     * Delete all user data for all users in the specified context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('optiksinav', $context->instanceid);
        if (!$cm) {
            return;
        }
        self::delete_attempts_where($DB->get_fieldset_select(
            'optiksinav_attempt',
            'id',
            'optiksinavid = ?',
            [$cm->instance]
        ));
    }

    /**
     * Delete all data for the user in the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('optiksinav', $context->instanceid);
            if (!$cm) {
                continue;
            }
            self::delete_attempts_where($DB->get_fieldset_select(
                'optiksinav_attempt',
                'id',
                'optiksinavid = ? AND userid = ?',
                [$cm->instance, $userid]
            ));
        }
    }

    /**
     * Delete data for multiple users in a single context.
     *
     * @param approved_userlist $userlist The approved users and context.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('optiksinav', $context->instanceid);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['oid'] = $cm->instance;
        $attemptids = $DB->get_fieldset_select(
            'optiksinav_attempt',
            'id',
            "optiksinavid = :oid AND userid $insql",
            $params
        );
        self::delete_attempts_where($attemptids);
    }

    /**
     * Delete the given attempts and their answers.
     *
     * @param array $attemptids Attempt ids to delete.
     */
    private static function delete_attempts_where(array $attemptids) {
        global $DB;
        if (!$attemptids) {
            return;
        }
        $DB->delete_records_list('optiksinav_attempt_answer', 'attemptid', $attemptids);
        $DB->delete_records_list('optiksinav_attempt', 'id', $attemptids);
    }
}
