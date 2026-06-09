<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AJAX endpoint: save a single answer for an in-progress attempt.
 *
 * Returns JSON { status: 'ok'|'expired'|'error', answered, blank }.
 * Identity comes from the Moodle session ($USER) — never from the request,
 * so a student can only ever write to their own attempt.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$attemptid = required_param('attemptid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$answer = optional_param('answer', '', PARAM_ALPHA);

$attempt = $DB->get_record('optiksinav_attempt', ['id' => $attemptid], '*', MUST_EXIST);
$instance = $DB->get_record('optiksinav', ['id' => $attempt->optiksinavid], '*', MUST_EXIST);
list($course, $cm) = get_course_and_cm_from_instance($instance->id, 'optiksinav');

require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/optiksinav:attempt', $context);

header('Content-Type: application/json; charset=utf-8');

// Safe Exam Browser zorunluysa ve sağlanmıyorsa yazma.
if (!optiksinav_seb_satisfied($instance)) {
    echo json_encode(['status' => 'error', 'message' => 'sebrequired']);
    die();
}

// Yetki: yalnızca denemenin sahibi yazabilir.
if ((int)$attempt->userid !== (int)$USER->id) {
    echo json_encode(['status' => 'error', 'message' => 'forbidden']);
    die();
}

// Sadece devam eden ve süresi geçmemiş denemeye yazılabilir (sunucu otoritesi).
if ($attempt->state !== OPTIKSINAV_STATE_INPROGRESS || optiksinav_attempt_overdue($attempt)) {
    echo json_encode(['status' => 'expired']);
    die();
}

// Slot aralık kontrolü.
if ($slot < 1 || $slot > $instance->questioncount) {
    echo json_encode(['status' => 'error', 'message' => 'badslot']);
    die();
}

// Geçerli şık mı? (boş = temizle)
$letters = optiksinav_get_option_letters($instance->optioncount);
if ($answer !== '' && !in_array(strtoupper($answer), $letters, true)) {
    echo json_encode(['status' => 'error', 'message' => 'badanswer']);
    die();
}

optiksinav_save_attempt_answer($attemptid, $slot, $answer);

$answers = optiksinav_get_attempt_answers($attemptid);
$answered = 0;
foreach ($answers as $a) {
    if ($a !== null && $a !== '') {
        $answered++;
    }
}

echo json_encode([
    'status' => 'ok',
    'answered' => $answered,
    'blank' => $instance->questioncount - $answered,
]);
