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
 * Privacy provider tests for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_optiksinav\privacy\provider
 */

namespace mod_optiksinav\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/optiksinav/locallib.php');

/**
 * Tests for the mod_optiksinav privacy provider (export and deletion of user data).
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Build a course + exam + a finished attempt for the given user.
     *
     * @param \stdClass $user
     * @return array{0:\context_module,1:\stdClass} The module context and instance.
     */
    private function create_attempt_for(\stdClass $user): array {
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module(
            'optiksinav',
            ['course' => $course->id, 'questioncount' => 3]
        );
        optiksinav_save_answer_key($instance->id, 3, [1 => 'A', 2 => 'B', 3 => 'C']);
        $attempt = optiksinav_start_attempt($instance, $user->id);
        optiksinav_save_attempt_answer($attempt->id, 1, 'A');
        optiksinav_save_attempt_answer($attempt->id, 2, 'D');
        optiksinav_grade_attempt($attempt, $instance);
        $cm = get_coursemodule_from_instance('optiksinav', $instance->id);
        return [\context_module::instance($cm->id), $instance];
    }

    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        [$context] = $this->create_attempt_for($user);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($context->id, $contextlist->get_contextids()[0]);

        // A different user with no attempts has no contexts.
        $other = $this->getDataGenerator()->create_user();
        $this->assertCount(0, provider::get_contexts_for_userid($other->id));
    }

    public function test_export_user_data(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        [$context] = $this->create_attempt_for($user);

        $this->export_context_data_for_user($user->id, $context, 'mod_optiksinav');
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
    }

    public function test_delete_data_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        [$context, $instance] = $this->create_attempt_for($user);

        $this->assertEquals(
            1,
            $DB->count_records('optiksinav_attempt', ['optiksinavid' => $instance->id])
        );

        $approved = new approved_contextlist($user, 'mod_optiksinav', [$context->id]);
        provider::delete_data_for_user($approved);

        $this->assertEquals(
            0,
            $DB->count_records('optiksinav_attempt', ['optiksinavid' => $instance->id])
        );
        $this->assertEquals(0, $DB->count_records_sql(
            "SELECT COUNT(*) FROM {optiksinav_attempt_answer} aa
               JOIN {optiksinav_attempt} a ON a.id = aa.attemptid
              WHERE a.optiksinavid = ?",
            [$instance->id]
        ));
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        [$context, $instance] = $this->create_attempt_for($user);

        provider::delete_data_for_all_users_in_context($context);
        $this->assertEquals(
            0,
            $DB->count_records('optiksinav_attempt', ['optiksinavid' => $instance->id])
        );
    }

    public function test_get_users_in_context(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        [$context] = $this->create_attempt_for($user);

        $userlist = new \core_privacy\local\request\userlist($context, 'mod_optiksinav');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$user->id], $userlist->get_userids());
    }
}
