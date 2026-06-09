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
 * Backup structure step for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete backup tree for one optiksinav instance.
 */
class backup_optiksinav_activity_structure_step extends backup_activity_structure_step {
    /**
     * Build the backup structure.
     *
     * @return backup_nested_element The root element wrapped for the activity.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $optiksinav = new backup_nested_element('optiksinav', ['id'], [
            'name', 'intro', 'introformat', 'timelimit', 'timeopen', 'timeclose',
            'questioncount', 'optioncount', 'penaltyratio', 'grade', 'timecreated', 'timemodified',
        ]);

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], ['slot', 'correctanswer', 'points']);

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid', 'attemptnumber', 'state', 'timestart', 'timefinish', 'timedue',
            'correctcount', 'wrongcount', 'blankcount', 'net', 'grade', 'timemodified',
        ]);

        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', ['id'], ['slot', 'answer']);

        // Tree.
        $optiksinav->add_child($questions);
        $questions->add_child($question);
        $optiksinav->add_child($attempts);
        $attempts->add_child($attempt);
        $attempt->add_child($answers);
        $answers->add_child($answer);

        // Sources.
        $optiksinav->set_source_table('optiksinav', ['id' => backup::VAR_ACTIVITYID]);
        $question->set_source_table('optiksinav_question', ['optiksinavid' => backup::VAR_PARENTID]);

        if ($userinfo) {
            $attempt->set_source_table('optiksinav_attempt', ['optiksinavid' => backup::VAR_PARENTID]);
            $answer->set_source_table('optiksinav_attempt_answer', ['attemptid' => backup::VAR_PARENTID]);
        }

        // Id annotations.
        $attempt->annotate_ids('user', 'userid');

        // File annotations.
        $optiksinav->annotate_files('mod_optiksinav', 'exampdf', null);
        $optiksinav->annotate_files('mod_optiksinav', 'answerpdf', null);

        return $this->prepare_activity_structure($optiksinav);
    }
}
