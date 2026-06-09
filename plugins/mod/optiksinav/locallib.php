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

/** Attempt state: the student is still working. */
define('OPTIKSINAV_STATE_INPROGRESS', 'inprogress');

/** Attempt state: the attempt is submitted and graded. */
define('OPTIKSINAV_STATE_FINISHED', 'finished');

/** Attempt state: the attempt was abandoned (closed without finishing). */
define('OPTIKSINAV_STATE_ABANDONED', 'abandoned');

/** Request header Safe Exam Browser sends on every request. */
define('OPTIKSINAV_SEB_HEADER', 'HTTP_X_SAFEEXAMBROWSER_REQUESTHASH');

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

/**
 * Compute the effective deadline (unix time) for an attempt that starts now.
 *
 * The deadline is the earliest of (start + time limit) and the exam close time.
 *
 * @param stdClass $instance The optiksinav instance.
 * @param int $timestart Attempt start time (unix).
 * @return int Deadline unix time, or 0 when there is no deadline at all.
 */
function optiksinav_effective_deadline(stdClass $instance, int $timestart): int {
    $deadlines = [];
    if ($instance->timelimit > 0) {
        $deadlines[] = $timestart + $instance->timelimit;
    }
    if ($instance->timeclose > 0) {
        $deadlines[] = $instance->timeclose;
    }
    return $deadlines ? min($deadlines) : 0;
}

/**
 * Return the user's current attempt for an exam (most recent), or null.
 *
 * @param int $optiksinavid The optiksinav instance id.
 * @param int $userid The user id.
 * @return stdClass|null The attempt record, or null when none exists.
 */
function optiksinav_get_user_attempt(int $optiksinavid, int $userid): ?stdClass {
    global $DB;
    $records = $DB->get_records('optiksinav_attempt',
        ['optiksinavid' => $optiksinavid, 'userid' => $userid], 'attemptnumber DESC', '*', 0, 1);
    return $records ? reset($records) : null;
}

/**
 * Whether the exam is within its open/close window right now.
 *
 * @param stdClass $instance The optiksinav instance.
 * @param int|null $now Time to test (defaults to now).
 * @return bool True when the exam is currently accessible.
 */
function optiksinav_is_open(stdClass $instance, ?int $now = null): bool {
    $now = $now ?? time();
    if ($instance->timeopen > 0 && $now < $instance->timeopen) {
        return false;
    }
    if ($instance->timeclose > 0 && $now > $instance->timeclose) {
        return false;
    }
    return true;
}

/**
 * Whether an in-progress attempt has passed its deadline.
 *
 * @param stdClass $attempt The attempt record.
 * @param int|null $now Time to test (defaults to now).
 * @return bool True when the attempt is overdue and should be auto-submitted.
 */
function optiksinav_attempt_overdue(stdClass $attempt, ?int $now = null): bool {
    $now = $now ?? time();
    return $attempt->timedue > 0 && $now > $attempt->timedue;
}

/**
 * Create and persist a fresh in-progress attempt for a user.
 *
 * @param stdClass $instance The optiksinav instance.
 * @param int $userid The user id.
 * @return stdClass The created attempt record (with id populated).
 */
function optiksinav_start_attempt(stdClass $instance, int $userid): stdClass {
    global $DB;
    $now = time();

    $previous = $DB->count_records('optiksinav_attempt',
        ['optiksinavid' => $instance->id, 'userid' => $userid]);

    $attempt = (object)[
        'optiksinavid' => $instance->id,
        'userid' => $userid,
        'attemptnumber' => $previous + 1,
        'state' => OPTIKSINAV_STATE_INPROGRESS,
        'timestart' => $now,
        'timefinish' => 0,
        'timedue' => optiksinav_effective_deadline($instance, $now),
        'correctcount' => 0,
        'wrongcount' => 0,
        'blankcount' => 0,
        'net' => 0,
        'grade' => null,
        'timemodified' => $now,
    ];
    $attempt->id = $DB->insert_record('optiksinav_attempt', $attempt);
    return $attempt;
}

/**
 * Load a student's saved answers for an attempt as slot => answer.
 *
 * @param int $attemptid The attempt id.
 * @return array<int,string|null> Map of slot to answer letter.
 */
function optiksinav_get_attempt_answers(int $attemptid): array {
    global $DB;
    $records = $DB->get_records('optiksinav_attempt_answer', ['attemptid' => $attemptid], 'slot ASC',
        'slot, answer');
    $answers = [];
    foreach ($records as $record) {
        $answers[(int)$record->slot] = $record->answer;
    }
    return $answers;
}

/**
 * Upsert a single answer for an in-progress attempt.
 *
 * @param int $attemptid The attempt id.
 * @param int $slot The question slot (1..N).
 * @param string|null $answer The chosen option letter, or '' / null to clear.
 * @return void
 */
function optiksinav_save_attempt_answer(int $attemptid, int $slot, ?string $answer): void {
    global $DB;
    $answer = ($answer === null || $answer === '') ? null : strtoupper($answer);

    $existing = $DB->get_record('optiksinav_attempt_answer',
        ['attemptid' => $attemptid, 'slot' => $slot]);
    if ($existing) {
        $DB->set_field('optiksinav_attempt_answer', 'answer', $answer, ['id' => $existing->id]);
    } else {
        $DB->insert_record('optiksinav_attempt_answer', (object)[
            'attemptid' => $attemptid,
            'slot' => $slot,
            'answer' => $answer,
        ]);
    }
}

/**
 * Score an attempt against the answer key and persist the result, marking it finished.
 *
 * Only slots with a defined correct answer are graded. Net = correct − wrong × penalty.
 * The gradebook value is scaled into the activity's max grade; the actual gradebook push
 * is wired in Faz 3.
 *
 * @param stdClass $attempt The in-progress attempt record.
 * @param stdClass $instance The optiksinav instance.
 * @return stdClass The updated attempt record (finished).
 */
function optiksinav_grade_attempt(stdClass $attempt, stdClass $instance): stdClass {
    global $DB;

    $key = optiksinav_get_answer_key($instance->id);
    $answers = optiksinav_get_attempt_answers($attempt->id);

    $correct = 0;
    $wrong = 0;
    $blank = 0;
    $graded = 0;

    for ($slot = 1; $slot <= $instance->questioncount; $slot++) {
        $correctanswer = $key[$slot] ?? null;
        if ($correctanswer === null || $correctanswer === '') {
            continue; // Anahtarı girilmemiş slot puanlamaya katılmaz.
        }
        $graded++;
        $given = $answers[$slot] ?? '';
        if ($given === '' || $given === null) {
            $blank++;
        } else if (strtoupper($given) === strtoupper($correctanswer)) {
            $correct++;
        } else {
            $wrong++;
        }
    }

    $net = $correct - ($wrong * (float)$instance->penaltyratio);
    $grade = $graded > 0 ? max(0, $net) / $graded * (float)$instance->grade : null;

    $now = time();
    $attempt->state = OPTIKSINAV_STATE_FINISHED;
    $attempt->timefinish = $now;
    $attempt->correctcount = $correct;
    $attempt->wrongcount = $wrong;
    $attempt->blankcount = $blank;
    $attempt->net = $net;
    $attempt->grade = $grade;
    $attempt->timemodified = $now;
    $DB->update_record('optiksinav_attempt', $attempt);

    // Net puanı Moodle not defterine işle.
    require_once(__DIR__.'/lib.php');
    optiksinav_update_grades($instance, $attempt->userid);

    return $attempt;
}

/**
 * Whether the Safe Exam Browser requirement (if any) is satisfied for this request.
 *
 * This is a pragmatic gate: when the exam requires SEB, the request must carry the
 * SEB request-hash header (or a SEB user agent). Full config-key/browser-exam-key
 * validation is intentionally out of scope and would be layered on top of this.
 *
 * @param stdClass $instance The optiksinav instance.
 * @return bool True when access is allowed.
 */
function optiksinav_seb_satisfied(stdClass $instance): bool {
    if (empty($instance->requireseb)) {
        return true;
    }
    if (!empty($_SERVER[OPTIKSINAV_SEB_HEADER])) {
        return true;
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return stripos($ua, 'SEB') !== false;
}

/**
 * Increment the focus-loss (tab-switch) counter for an in-progress attempt.
 *
 * @param int $attemptid The attempt id.
 * @return void
 */
function optiksinav_increment_focusloss(int $attemptid): void {
    global $DB;
    $DB->execute('UPDATE {optiksinav_attempt} SET focuslosscount = focuslosscount + 1 WHERE id = ?',
        [$attemptid]);
}
