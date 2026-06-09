<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup activity task for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/optiksinav/backup/moodle2/backup_optiksinav_stepslib.php');

/**
 * Backup task that provides the settings and steps to back up an optiksinav activity.
 */
class backup_optiksinav_activity_task extends backup_activity_task {

    /**
     * No activity-specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Define the activity backup steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_optiksinav_activity_structure_step('optiksinav_structure', 'optiksinav.xml'));
    }

    /**
     * Encode links to this module so they can be restored portably.
     *
     * @param string $content The content to encode.
     * @return string The encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');

        // /mod/optiksinav/view.php?id=...
        $search = '/('.$base.'\/mod\/optiksinav\/view\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@OPTIKSINAVVIEWBYID*$2@$', $content);

        // /mod/optiksinav/index.php?id=...
        $search = '/('.$base.'\/mod\/optiksinav\/index\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@OPTIKSINAVINDEX*$2@$', $content);

        return $content;
    }
}
