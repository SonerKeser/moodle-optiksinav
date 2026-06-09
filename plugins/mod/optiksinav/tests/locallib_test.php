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
 * Unit tests for mod_optiksinav core logic (scoring, answer key, attempts).
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \optiksinav_grade_attempt
 */

namespace mod_optiksinav;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/optiksinav/locallib.php');
require_once($CFG->dirroot . '/mod/optiksinav/lib.php');

/**
 * Unit tests for the scoring, answer-key and attempt helpers in locallib.php.
 *
 * @covers \optiksinav_grade_attempt
 */
final class locallib_test extends \advanced_testcase {
    /** @var \stdClass The test course. */
    private $course;

    /** @var \stdClass The optiksinav instance. */
    private $instance;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->instance = $this->getDataGenerator()->create_module('optiksinav', [
            'course' => $this->course->id,
            'questioncount' => 5,
            'optioncount' => 5,
            'penaltyratio' => 0.25,
            'grade' => 100,
        ]);
    }

    public function test_option_letters(): void {
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], optiksinav_get_option_letters(5));
        $this->assertSame(['A', 'B'], optiksinav_get_option_letters(1)); // Clamped to min 2.
        $this->assertCount(12, optiksinav_get_option_letters(99));       // Clamped to max 12.
    }

    public function test_answer_key_upsert_and_orphan_cleanup(): void {
        optiksinav_save_answer_key(
            $this->instance->id,
            5,
            [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E']
        );
        $key = optiksinav_get_answer_key($this->instance->id);
        $this->assertSame('A', $key[1]);
        $this->assertSame('E', $key[5]);
        $this->assertSame(5, optiksinav_answer_key_filled_count($this->instance->id));

        // Reduce to 3 questions, blank slot 2: orphans (4,5) must disappear.
        optiksinav_save_answer_key($this->instance->id, 3, [1 => 'A', 2 => '', 3 => 'C']);
        $key = optiksinav_get_answer_key($this->instance->id);
        $this->assertCount(3, $key);
        $this->assertNull($key[2]);
        $this->assertSame(2, optiksinav_answer_key_filled_count($this->instance->id));
    }

    public function test_effective_deadline(): void {
        $start = 1000000;
        // Time limit only.
        $i = (object)['timelimit' => 600, 'timeclose' => 0];
        $this->assertSame(1000600, optiksinav_effective_deadline($i, $start));
        // Close time earlier than limit wins.
        $i = (object)['timelimit' => 3600, 'timeclose' => 1000500];
        $this->assertSame(1000500, optiksinav_effective_deadline($i, $start));
        // No constraints.
        $i = (object)['timelimit' => 0, 'timeclose' => 0];
        $this->assertSame(0, optiksinav_effective_deadline($i, $start));
    }

    public function test_grade_attempt_net_scoring(): void {
        global $DB;
        optiksinav_save_answer_key(
            $this->instance->id,
            5,
            [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E']
        );

        $user = $this->getDataGenerator()->create_user();
        $attempt = optiksinav_start_attempt($this->instance, $user->id);

        // 3 correct (1,2,5), 1 wrong (3), 1 blank (4).
        optiksinav_save_attempt_answer($attempt->id, 1, 'A');
        optiksinav_save_attempt_answer($attempt->id, 2, 'B');
        optiksinav_save_attempt_answer($attempt->id, 3, 'A'); // wrong (key C).
        optiksinav_save_attempt_answer($attempt->id, 5, 'E');

        $graded = optiksinav_grade_attempt($attempt, $this->instance);

        $this->assertSame(OPTIKSINAV_STATE_FINISHED, $graded->state);
        $this->assertEquals(3, $graded->correctcount);
        $this->assertEquals(1, $graded->wrongcount);
        $this->assertEquals(1, $graded->blankcount);
        // Net = 3 - 1*0.25 = 2.75.
        $this->assertEqualsWithDelta(2.75, (float)$graded->net, 0.0001);
        // Grade = 2.75 / 5 * 100 = 55.
        $this->assertEqualsWithDelta(55.0, (float)$graded->grade, 0.0001);

        // Persisted.
        $stored = $DB->get_record('optiksinav_attempt', ['id' => $attempt->id]);
        $this->assertSame('finished', $stored->state);
    }

    public function test_unkeyed_slots_are_not_graded(): void {
        // Only slots 1 and 2 have a key; 3-5 are unset.
        optiksinav_save_answer_key($this->instance->id, 5, [1 => 'A', 2 => 'B']);
        $user = $this->getDataGenerator()->create_user();
        $attempt = optiksinav_start_attempt($this->instance, $user->id);
        optiksinav_save_attempt_answer($attempt->id, 1, 'A'); // correct.
        // Slot 2 blank; slots 3-5 unkeyed (ignored).

        $graded = optiksinav_grade_attempt($attempt, $this->instance);
        $this->assertEquals(1, $graded->correctcount);
        $this->assertEquals(0, $graded->wrongcount);
        $this->assertEquals(1, $graded->blankcount); // Only slot 2 counts as blank.
    }

    public function test_attempt_overdue_detection(): void {
        $attempt = (object)['timedue' => 100];
        $this->assertTrue(optiksinav_attempt_overdue($attempt, 200));
        $this->assertFalse(optiksinav_attempt_overdue($attempt, 50));
        $nodeadline = (object)['timedue' => 0];
        $this->assertFalse(optiksinav_attempt_overdue($nodeadline, PHP_INT_MAX));
    }

    public function test_grade_pushed_to_gradebook(): void {
        require_once(__DIR__ . '/../lib.php');
        optiksinav_save_answer_key(
            $this->instance->id,
            5,
            [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E']
        );
        $user = $this->getDataGenerator()->create_user();
        $attempt = optiksinav_start_attempt($this->instance, $user->id);
        optiksinav_save_attempt_answer($attempt->id, 1, 'A');
        optiksinav_grade_attempt($attempt, $this->instance);

        $grades = grade_get_grades($this->course->id, 'mod', 'optiksinav', $this->instance->id, $user->id);
        $item = reset($grades->items);
        $this->assertEqualsWithDelta(20.0, (float)$item->grades[$user->id]->grade, 0.0001);
        // 1 correct of 5 keyed → net 1 → 1/5*100 = 20.
    }
}
