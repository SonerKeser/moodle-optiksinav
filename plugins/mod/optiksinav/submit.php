<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Finalises (grades) an in-progress attempt and redirects to the review page.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$id = required_param('id', PARAM_INT);             // Course module id.
$attemptid = required_param('attemptid', PARAM_INT);

$cm = get_coursemodule_from_id('optiksinav', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('optiksinav', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/optiksinav:attempt', $context);

$attempt = $DB->get_record('optiksinav_attempt', ['id' => $attemptid], '*', MUST_EXIST);

$reviewurl = new moodle_url('/mod/optiksinav/review.php', ['id' => $cm->id]);

// Yetki + tutarlılık: deneme bu kullanıcıya ve bu sınava ait olmalı.
if ((int)$attempt->userid !== (int)$USER->id || (int)$attempt->optiksinavid !== (int)$instance->id) {
    throw new moodle_exception('invalidaccess', 'optiksinav');
}

// Zaten bitmişse tekrar puanlama.
if ($attempt->state === OPTIKSINAV_STATE_INPROGRESS) {
    optiksinav_grade_attempt($attempt, $instance);
}

redirect($reviewurl, get_string('attemptsubmitted', 'optiksinav'), null,
    \core\output\notification::NOTIFY_SUCCESS);
