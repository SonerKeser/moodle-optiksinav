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
 * Scheduled task: auto-submit attempts whose deadline has passed.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_optiksinav\task;

/**
 * Finishes and grades any in-progress attempt that is past its server-side deadline,
 * so a score is recorded even if the student never returns to the page.
 */
class close_overdue_attempts extends \core\task\scheduled_task {
    /**
     * Return the human-readable name of this scheduled task.
     *
     * @return string The visible name of this task.
     */
    public function get_name() {
        return get_string('task_closeoverdue', 'optiksinav');
    }

    /**
     * Grade every overdue in-progress attempt.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/optiksinav/locallib.php');

        $now = time();
        $rs = $DB->get_recordset_select(
            'optiksinav_attempt',
            "state = :state AND timedue > 0 AND timedue < :now",
            ['state' => OPTIKSINAV_STATE_INPROGRESS, 'now' => $now]
        );

        $closed = 0;
        $instances = [];
        foreach ($rs as $attempt) {
            if (!isset($instances[$attempt->optiksinavid])) {
                $instances[$attempt->optiksinavid] =
                    $DB->get_record('optiksinav', ['id' => $attempt->optiksinavid]);
            }
            $instance = $instances[$attempt->optiksinavid];
            if (!$instance) {
                continue;
            }
            optiksinav_grade_attempt($attempt, $instance);
            $closed++;
        }
        $rs->close();

        if ($closed > 0) {
            mtrace("mod_optiksinav: closed {$closed} overdue attempt(s).");
        }
    }
}
