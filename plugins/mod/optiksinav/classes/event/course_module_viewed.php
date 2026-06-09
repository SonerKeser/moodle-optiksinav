<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * The mod_optiksinav course module viewed event.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_optiksinav\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Fired when an Optical exam activity is viewed.
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'optiksinav';
    }

    /**
     * Mapping for the object id used during backup/restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'optiksinav', 'restore' => 'optiksinav'];
    }
}
