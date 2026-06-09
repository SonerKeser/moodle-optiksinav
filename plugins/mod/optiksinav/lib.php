<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Library of interface functions and constants for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Declare which optional features the module supports.
 *
 * @param string $feature FEATURE_xxx constant.
 * @return mixed True/false for supported features, null for unknown.
 */
function optiksinav_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Create a new exam instance.
 *
 * @param stdClass $data Form data from mod_form.
 * @param mod_optiksinav_mod_form|null $mform The form instance.
 * @return int The id of the newly inserted record.
 */
function optiksinav_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    $data->intro = $data->intro ?? '';
    $data->introformat = $data->introformat ?? FORMAT_HTML;

    $data->id = $DB->insert_record('optiksinav', $data);

    return $data->id;
}

/**
 * Update an existing exam instance.
 *
 * @param stdClass $data Form data from mod_form (contains instance id in $data->instance).
 * @param mod_optiksinav_mod_form|null $mform The form instance.
 * @return bool True on success.
 */
function optiksinav_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    return $DB->update_record('optiksinav', $data);
}

/**
 * Delete an exam instance and all associated data.
 *
 * @param int $id The instance id.
 * @return bool True on success.
 */
function optiksinav_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('optiksinav', ['id' => $id])) {
        return false;
    }

    // Faz 1+: ilişkili sorular ve denemeler de burada silinecek.
    $DB->delete_records('optiksinav', ['id' => $id]);

    return true;
}
