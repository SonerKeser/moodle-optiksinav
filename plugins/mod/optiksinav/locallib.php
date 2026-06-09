<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Internal helper library for mod_optiksinav (loaded only where needed).
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** File area: the exam question booklet PDF (one per instance). */
define('OPTIKSINAV_FILEAREA_EXAM', 'exampdf');

/** File area: the answer/solution booklet PDF (one per instance). */
define('OPTIKSINAV_FILEAREA_ANSWER', 'answerpdf');

/** Itemid used for the single-file-per-activity areas. */
define('OPTIKSINAV_FILE_ITEMID', 0);

/**
 * Return the answer-option letters for a given option count, e.g. 5 → ['A','B','C','D','E'].
 *
 * @param int $optioncount Number of options (clamped to 2..12).
 * @return string[] Ordered list of single-letter option labels.
 */
function optiksinav_get_option_letters(int $optioncount): array {
    $optioncount = max(2, min(12, $optioncount));
    $letters = [];
    for ($i = 0; $i < $optioncount; $i++) {
        $letters[] = chr(ord('A') + $i);
    }
    return $letters;
}

/**
 * Load the answer key for an exam as a slot => correctanswer map.
 *
 * @param int $optiksinavid The optiksinav instance id.
 * @return array<int,string|null> Map of slot number to correct answer letter (or null if unset).
 */
function optiksinav_get_answer_key(int $optiksinavid): array {
    global $DB;
    $records = $DB->get_records('optiksinav_question', ['optiksinavid' => $optiksinavid], 'slot ASC',
        'slot, correctanswer');
    $key = [];
    foreach ($records as $record) {
        $key[(int)$record->slot] = $record->correctanswer;
    }
    return $key;
}

/**
 * Persist the answer key for an exam, upserting one row per slot.
 *
 * Slots 1..questioncount are written; any extra rows (e.g. after reducing the
 * question count) are removed so the table never holds orphan slots.
 *
 * @param int $optiksinavid The optiksinav instance id.
 * @param int $questioncount Number of question slots to maintain.
 * @param array<int,string|null> $answers Map of slot => answer letter (null/'' = unset).
 * @return void
 */
function optiksinav_save_answer_key(int $optiksinavid, int $questioncount, array $answers): void {
    global $DB;

    $existing = $DB->get_records('optiksinav_question', ['optiksinavid' => $optiksinavid], '', 'slot, id');

    for ($slot = 1; $slot <= $questioncount; $slot++) {
        $answer = isset($answers[$slot]) && $answers[$slot] !== '' ? strtoupper($answers[$slot]) : null;
        if (isset($existing[$slot])) {
            $DB->set_field('optiksinav_question', 'correctanswer', $answer,
                ['id' => $existing[$slot]->id]);
            unset($existing[$slot]);
        } else {
            $DB->insert_record('optiksinav_question', (object)[
                'optiksinavid' => $optiksinavid,
                'slot' => $slot,
                'correctanswer' => $answer,
                'points' => 1,
            ]);
        }
    }

    // Remove slots beyond the current question count.
    foreach ($existing as $slot => $record) {
        $DB->delete_records('optiksinav_question', ['id' => $record->id]);
    }
}

/**
 * Whether a stored file exists in one of this module's single-file areas.
 *
 * @param context_module $context The module context.
 * @param string $filearea Either OPTIKSINAV_FILEAREA_EXAM or OPTIKSINAV_FILEAREA_ANSWER.
 * @return bool True if a non-directory file is present.
 */
function optiksinav_has_file(context_module $context, string $filearea): bool {
    $fs = get_file_storage();
    return $fs->get_area_files($context->id, 'mod_optiksinav', $filearea, OPTIKSINAV_FILE_ITEMID,
        'itemid, filepath, filename', false) ? true : false;
}

/**
 * Build a pluginfile URL for the single stored file in a module file area, or null if none.
 *
 * @param context_module $context The module context.
 * @param string $filearea Either OPTIKSINAV_FILEAREA_EXAM or OPTIKSINAV_FILEAREA_ANSWER.
 * @return moodle_url|null URL to stream the file, or null when no file is stored.
 */
function optiksinav_get_file_url(context_module $context, string $filearea): ?moodle_url {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_optiksinav', $filearea, OPTIKSINAV_FILE_ITEMID,
        'itemid, filepath, filename', false);
    if (!$files) {
        return null;
    }
    $file = reset($files);
    return moodle_url::make_pluginfile_url($context->id, 'mod_optiksinav', $filearea,
        OPTIKSINAV_FILE_ITEMID, $file->get_filepath(), $file->get_filename());
}

/**
 * How many answer-key slots have a correct answer assigned.
 *
 * @param int $optiksinavid The optiksinav instance id.
 * @return int Count of slots with a non-empty correct answer.
 */
function optiksinav_answer_key_filled_count(int $optiksinavid): int {
    global $DB;
    return $DB->count_records_select('optiksinav_question',
        'optiksinavid = :id AND correctanswer IS NOT NULL AND correctanswer <> :empty',
        ['id' => $optiksinavid, 'empty' => '']);
}
