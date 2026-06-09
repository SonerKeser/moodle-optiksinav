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
 * Restore activity task for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/optiksinav/backup/moodle2/restore_optiksinav_stepslib.php');

/**
 * Restore task that provides the settings and steps to restore an optiksinav activity.
 */
class restore_optiksinav_activity_task extends restore_activity_task {
    /**
     * No activity-specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define the activity restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_optiksinav_activity_structure_step('optiksinav_structure', 'optiksinav.xml'));
    }

    /**
     * Define the contents (file areas) handled by this activity for restore.
     *
     * @return array The restore_decode_content elements.
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('optiksinav', ['intro'], 'optiksinav'),
        ];
    }

    /**
     * Define the decoding rules for content links.
     *
     * @return array The restore_decode_rule elements.
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('OPTIKSINAVVIEWBYID', '/mod/optiksinav/view.php?id=$1', 'course_module'),
            new restore_decode_rule('OPTIKSINAVINDEX', '/mod/optiksinav/index.php?id=$1', 'course'),
        ];
    }
}
