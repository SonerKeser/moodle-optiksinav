<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Prints the main view of an Optical exam activity.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

// Course module id, or the instance id.
$id = optional_param('id', 0, PARAM_INT);
$o  = optional_param('o', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('optiksinav', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('optiksinav', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('optiksinav', ['id' => $o], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('optiksinav', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/optiksinav:view', $modulecontext);

// Log the view (completion + analytics) — Faz 2'de event sınıfı eklenecek.
$PAGE->set_url('/mod/optiksinav/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moduleinstance->name));

if (!empty($moduleinstance->intro)) {
    echo $OUTPUT->box(format_module_intro('optiksinav', $moduleinstance, $cm->id), 'generalbox', 'intro');
}

// Faz 0 yer tutucu — sınav deneyimi Faz 2'de gelecek.
echo $OUTPUT->notification(
    get_string('questioncount', 'optiksinav') . ': ' . $moduleinstance->questioncount,
    \core\output\notification::NOTIFY_INFO
);

echo $OUTPUT->footer();
