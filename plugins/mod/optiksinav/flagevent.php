<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AJAX endpoint: record an anti-cheat signal (currently: a focus/tab-switch loss).
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$attemptid = required_param('attemptid', PARAM_INT);
$event = required_param('event', PARAM_ALPHA);

$attempt = $DB->get_record('optiksinav_attempt', ['id' => $attemptid], '*', MUST_EXIST);
$instance = $DB->get_record('optiksinav', ['id' => $attempt->optiksinavid], '*', MUST_EXIST);
list($course, $cm) = get_course_and_cm_from_instance($instance->id, 'optiksinav');

require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/optiksinav:attempt', $context);

header('Content-Type: application/json; charset=utf-8');

if ((int)$attempt->userid !== (int)$USER->id
        || $attempt->state !== OPTIKSINAV_STATE_INPROGRESS
        || !optiksinav_seb_satisfied($instance)) {
    echo json_encode(['status' => 'ignored']);
    die();
}

if ($event === 'focusloss') {
    optiksinav_increment_focusloss($attemptid);
}

echo json_encode(['status' => 'ok']);
