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
 * Restore structure step for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restores one optiksinav instance from a backup file.
 */
class restore_optiksinav_activity_structure_step extends restore_activity_structure_step {
    /**
     * Declare the paths in the backup we are interested in restoring.
     *
     * @return array The restore path elements.
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('optiksinav', '/activity/optiksinav');
        $paths[] = new restore_path_element(
            'optiksinav_question',
            '/activity/optiksinav/questions/question'
        );

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'optiksinav_attempt',
                '/activity/optiksinav/attempts/attempt'
            );
            $paths[] = new restore_path_element(
                'optiksinav_attempt_answer',
                '/activity/optiksinav/attempts/attempt/answers/answer'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the main optiksinav record.
     *
     * @param array $data The element data.
     */
    protected function process_optiksinav($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if (!empty($data->timeopen)) {
            $data->timeopen = $this->apply_date_offset($data->timeopen);
        }
        if (!empty($data->timeclose)) {
            $data->timeclose = $this->apply_date_offset($data->timeclose);
        }

        $newid = $DB->insert_record('optiksinav', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restore one answer-key row.
     *
     * @param array $data The element data.
     */
    protected function process_optiksinav_question($data) {
        global $DB;
        $data = (object)$data;
        $data->optiksinavid = $this->get_new_parentid('optiksinav');
        $DB->insert_record('optiksinav_question', $data);
    }

    /**
     * Restore one attempt (mapping the user id).
     *
     * @param array $data The element data.
     */
    protected function process_optiksinav_attempt($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->optiksinavid = $this->get_new_parentid('optiksinav');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timefinish = $this->apply_date_offset($data->timefinish);
        $data->timedue = $this->apply_date_offset($data->timedue);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newid = $DB->insert_record('optiksinav_attempt', $data);
        $this->set_mapping('optiksinav_attempt', $oldid, $newid);
    }

    /**
     * Restore one attempt answer.
     *
     * @param array $data The element data.
     */
    protected function process_optiksinav_attempt_answer($data) {
        global $DB;
        $data = (object)$data;
        $data->attemptid = $this->get_new_parentid('optiksinav_attempt');
        $DB->insert_record('optiksinav_attempt_answer', $data);
    }

    /**
     * After restore: re-attach the PDF files to the new context.
     */
    protected function after_execute() {
        $this->add_related_files('mod_optiksinav', 'exampdf', null);
        $this->add_related_files('mod_optiksinav', 'answerpdf', null);
    }
}
