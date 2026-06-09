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
require_once(__DIR__.'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module id.
$o  = optional_param('o', 0, PARAM_INT);  // ... or optiksinav instance id.

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
$context = context_module::instance($cm->id);
require_capability('mod/optiksinav:view', $context);

// Completion + analitik için görüntüleme olayını işle.
$event = \mod_optiksinav\event\course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('optiksinav', $moduleinstance);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/optiksinav/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$canmanage = has_capability('mod/optiksinav:editanswerkey', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moduleinstance->name));

if (!empty($moduleinstance->intro)) {
    echo $OUTPUT->box(format_module_intro('optiksinav', $moduleinstance, $cm->id), 'generalbox', 'intro');
}

// --- Sınav özeti ---
$hasexampdf = optiksinav_has_file($context, OPTIKSINAV_FILEAREA_EXAM);
$hasanswerpdf = optiksinav_has_file($context, OPTIKSINAV_FILEAREA_ANSWER);
$keyfilled = optiksinav_answer_key_filled_count($moduleinstance->id);

$summary = new html_table();
$summary->attributes['class'] = 'generaltable';
$summary->data = [
    [get_string('questioncount', 'optiksinav'), $moduleinstance->questioncount],
    [get_string('optioncount', 'optiksinav'),
        implode(' ', optiksinav_get_option_letters($moduleinstance->optioncount))],
    [get_string('penaltyratio', 'optiksinav'), format_float($moduleinstance->penaltyratio, 2)],
    [get_string('timelimit', 'optiksinav'),
        $moduleinstance->timelimit ? format_time($moduleinstance->timelimit) : get_string('none')],
];
echo html_writer::table($summary);

// --- Öğretmen paneli: hazırlık durumu + düzenleme bağlantıları ---
if ($canmanage) {
    echo $OUTPUT->heading(get_string('teacherpanel', 'optiksinav'), 4);

    $checklist = html_writer::start_tag('ul');
    $checklist .= optiksinav_checklist_item($hasexampdf, get_string('status_exampdf', 'optiksinav'));
    $checklist .= optiksinav_checklist_item($hasanswerpdf, get_string('status_answerpdf', 'optiksinav'));
    $checklist .= optiksinav_checklist_item(
        $moduleinstance->questioncount > 0 && $keyfilled >= $moduleinstance->questioncount,
        get_string('status_answerkey', 'optiksinav',
            ['filled' => $keyfilled, 'total' => $moduleinstance->questioncount]));
    $checklist .= html_writer::end_tag('ul');
    echo html_writer::div($checklist, 'box generalbox');

    $editkeyurl = new moodle_url('/mod/optiksinav/editkey.php', ['id' => $cm->id]);
    echo html_writer::div(
        $OUTPUT->single_button($editkeyurl, get_string('editanswerkey', 'optiksinav'), 'get'),
        'mb-3');

    // Yüklü PDF'leri öğretmenin önizlemesi için bağlantılar.
    if ($examurl = optiksinav_get_file_url($context, OPTIKSINAV_FILEAREA_EXAM)) {
        echo html_writer::div(html_writer::link($examurl, get_string('preview_exampdf', 'optiksinav'),
            ['target' => '_blank']));
    }
    if ($answerurl = optiksinav_get_file_url($context, OPTIKSINAV_FILEAREA_ANSWER)) {
        echo html_writer::div(html_writer::link($answerurl, get_string('preview_answerpdf', 'optiksinav'),
            ['target' => '_blank']));
    }
} else if (has_capability('mod/optiksinav:attempt', $context)) {
    // --- Öğrenci görünümü: başlat / devam et / sonuç ---
    $attempt = optiksinav_get_user_attempt($moduleinstance->id, $USER->id);
    $attempturl = new moodle_url('/mod/optiksinav/attempt.php', ['id' => $cm->id]);
    $reviewurl = new moodle_url('/mod/optiksinav/review.php', ['id' => $cm->id]);

    if ($attempt && $attempt->state === OPTIKSINAV_STATE_FINISHED) {
        echo $OUTPUT->notification(get_string('alreadyfinished', 'optiksinav'),
            \core\output\notification::NOTIFY_INFO);
        echo $OUTPUT->single_button($reviewurl, get_string('viewresult', 'optiksinav'), 'get');
    } else if ($attempt && $attempt->state === OPTIKSINAV_STATE_INPROGRESS) {
        echo $OUTPUT->single_button($attempturl, get_string('resumeattempt', 'optiksinav'), 'get');
    } else if (!optiksinav_is_open($moduleinstance)) {
        echo $OUTPUT->notification(get_string('notopen', 'optiksinav'),
            \core\output\notification::NOTIFY_WARNING);
    } else if ($moduleinstance->questioncount < 1 || !$hasexampdf) {
        echo $OUTPUT->notification(get_string('notready', 'optiksinav'),
            \core\output\notification::NOTIFY_WARNING);
    } else {
        // Yeni deneme başlat (POST + sesskey).
        $starturl = new moodle_url('/mod/optiksinav/attempt.php',
            ['id' => $cm->id, 'action' => 'start']);
        echo $OUTPUT->single_button($starturl, get_string('startattempt', 'optiksinav'), 'post');
    }
}

echo $OUTPUT->footer();

/**
 * Render a single ✓/✗ checklist line.
 *
 * @param bool $ok Whether the item is satisfied.
 * @param string $label The item label.
 * @return string HTML list item.
 */
function optiksinav_checklist_item(bool $ok, string $label): string {
    $icon = $ok
        ? html_writer::tag('span', '✓ ', ['style' => 'color:green;font-weight:bold'])
        : html_writer::tag('span', '✗ ', ['style' => 'color:#cc0000;font-weight:bold']);
    return html_writer::tag('li', $icon.$label);
}
