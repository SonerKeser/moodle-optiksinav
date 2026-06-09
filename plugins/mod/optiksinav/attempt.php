<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * The exam-taking page: PDF booklet + optical answer sheet + server-enforced timer.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$id = required_param('id', PARAM_INT);              // Course module id.
$action = optional_param('action', '', PARAM_ALPHA); // 'start' to begin a new attempt.

$cm = get_coursemodule_from_id('optiksinav', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('optiksinav', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/optiksinav:attempt', $context);

$viewurl = new moodle_url('/mod/optiksinav/view.php', ['id' => $cm->id]);
$attempturl = new moodle_url('/mod/optiksinav/attempt.php', ['id' => $cm->id]);
$reviewurl = new moodle_url('/mod/optiksinav/review.php', ['id' => $cm->id]);

$attempt = optiksinav_get_user_attempt($instance->id, $USER->id);

// --- Yeni deneme başlatma (POST + sesskey) ---
if ($action === 'start' && confirm_sesskey()) {
    if ($attempt && $attempt->state === OPTIKSINAV_STATE_FINISHED) {
        redirect($reviewurl, get_string('alreadyfinished', 'optiksinav'));
    }
    if (!$attempt) {
        if (!optiksinav_is_open($instance)) {
            redirect($viewurl, get_string('notopen', 'optiksinav'), null,
                \core\output\notification::NOTIFY_ERROR);
        }
        if ($instance->questioncount < 1) {
            redirect($viewurl, get_string('noquestionsyet', 'optiksinav'), null,
                \core\output\notification::NOTIFY_ERROR);
        }
        $attempt = optiksinav_start_attempt($instance, $USER->id);
    }
    redirect($attempturl);
}

// Deneme yoksa: sınava giriş sayfasına yönlendir.
if (!$attempt) {
    redirect($viewurl);
}

// Bitmiş deneme: sonuç sayfasına.
if ($attempt->state === OPTIKSINAV_STATE_FINISHED) {
    redirect($reviewurl);
}

// Süresi geçmiş in-progress deneme: sunucu otomatik teslim alır.
if (optiksinav_attempt_overdue($attempt)) {
    optiksinav_grade_attempt($attempt, $instance);
    redirect($reviewurl, get_string('timeexpired', 'optiksinav'), null,
        \core\output\notification::NOTIFY_INFO);
}

// --- Devam eden denemeyi göster ---
$PAGE->set_url($attempturl);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$examurl = optiksinav_get_file_url($context, OPTIKSINAV_FILEAREA_EXAM);
$letters = optiksinav_get_option_letters($instance->optioncount);
$saved = optiksinav_get_attempt_answers($attempt->id);

// Kalan süre (saniye) — sunucu otoritesi. 0/negatif: teslim et.
$remaining = $attempt->timedue > 0 ? max(0, $attempt->timedue - time()) : 0;

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name));

echo html_writer::start_div('optiksinav-attempt row');

// Sol: PDF kitapçık.
echo html_writer::start_div('col-xl-8 mb-3');
if ($examurl) {
    echo html_writer::tag('iframe', '', [
        'src' => $examurl->out(false).'#toolbar=0&navpanes=0',
        'style' => 'width:100%;height:80vh;border:1px solid #ccc;',
        'title' => get_string('exampdf', 'optiksinav'),
    ]);
} else {
    echo $OUTPUT->notification(get_string('noexampdf', 'optiksinav'),
        \core\output\notification::NOTIFY_WARNING);
}
echo html_writer::end_div();

// Sağ: optik form + sayaç + timer.
echo html_writer::start_div('col-xl-4');

if ($attempt->timedue > 0) {
    echo html_writer::div('', 'optiksinav-timer alert alert-info text-center fw-bold',
        ['id' => 'optiksinav-timer']);
}

$answeredinit = 0;
foreach ($saved as $a) {
    if ($a !== null && $a !== '') {
        $answeredinit++;
    }
}
echo html_writer::start_div('mb-2');
echo html_writer::tag('span', get_string('answeredcount', 'optiksinav', $answeredinit),
    ['class' => 'badge bg-primary', 'id' => 'optiksinav-answered']);
echo ' ';
echo html_writer::tag('span',
    get_string('blankcount2', 'optiksinav', $instance->questioncount - $answeredinit),
    ['class' => 'badge bg-secondary', 'id' => 'optiksinav-blank']);
echo html_writer::end_div();

// Form (gerçek teslim submit.php'ye).
$formattrs = [
    'method' => 'post',
    'action' => (new moodle_url('/mod/optiksinav/submit.php'))->out(false),
    'id' => 'optiksinav-form',
];
echo html_writer::start_tag('form', $formattrs);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attemptid', 'value' => $attempt->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('optiksinav-sheet', ['style' => 'max-height:70vh;overflow-y:auto;']);
for ($slot = 1; $slot <= $instance->questioncount; $slot++) {
    echo html_writer::start_div('optiksinav-q d-flex align-items-center mb-2',
        ['data-slot' => $slot]);
    echo html_writer::tag('span', $slot.'.', ['class' => 'me-2 fw-bold', 'style' => 'width:2.5em;']);
    foreach ($letters as $letter) {
        $inputid = "q{$slot}_{$letter}";
        $checked = (isset($saved[$slot]) && $saved[$slot] === $letter) ? ['checked' => 'checked'] : [];
        echo html_writer::start_div('form-check form-check-inline');
        echo html_writer::empty_tag('input', array_merge([
            'class' => 'form-check-input optiksinav-opt',
            'type' => 'radio',
            'name' => "q[$slot]",
            'id' => $inputid,
            'value' => $letter,
            'data-slot' => $slot,
        ], $checked));
        echo html_writer::tag('label', $letter, ['class' => 'form-check-label', 'for' => $inputid]);
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}
echo html_writer::end_div(); // sheet.

echo html_writer::tag('button', get_string('finishattempt', 'optiksinav'),
    ['type' => 'submit', 'class' => 'btn btn-primary mt-3 w-100', 'id' => 'optiksinav-submit']);
echo html_writer::end_tag('form');
echo html_writer::end_div(); // col.
echo html_writer::end_div(); // row.

// İstemci JS: geri sayım + otomatik kaydetme + sayaçlar.
$PAGE->requires->js(new moodle_url('/mod/optiksinav/js/attempt.js'));
$jsconfig = [
    'cmid' => (int)$cm->id,
    'attemptid' => (int)$attempt->id,
    'sesskey' => sesskey(),
    'remaining' => (int)$remaining,
    'hasdeadline' => $attempt->timedue > 0,
    'questioncount' => (int)$instance->questioncount,
    'saveurl' => (new moodle_url('/mod/optiksinav/save.php'))->out(false),
    'strconfirm' => get_string('confirmfinish', 'optiksinav'),
    'strtimeup' => get_string('timeexpired', 'optiksinav'),
    'stranswered' => get_string('answeredcount', 'optiksinav', '__N__'),
    'strblank' => get_string('blankcount2', 'optiksinav', '__N__'),
];
echo html_writer::script('window.OPTIKSINAV_CONFIG = '.json_encode($jsconfig).';');

echo $OUTPUT->footer();
