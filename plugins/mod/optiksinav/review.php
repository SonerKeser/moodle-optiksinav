<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Shows the result of a finished attempt (own result for students; any for teachers).
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);                 // Course module id.
$attemptid = optional_param('attemptid', 0, PARAM_INT); // Optional: a specific attempt (teachers).

$cm = get_coursemodule_from_id('optiksinav', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('optiksinav', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$canviewreports = has_capability('mod/optiksinav:viewreports', $context);

if ($attemptid) {
    $attempt = $DB->get_record(
        'optiksinav_attempt',
        ['id' => $attemptid, 'optiksinavid' => $instance->id],
        '*',
        MUST_EXIST
    );
} else {
    $attempt = optiksinav_get_user_attempt($instance->id, $USER->id);
}

if (!$attempt) {
    redirect(new moodle_url('/mod/optiksinav/view.php', ['id' => $cm->id]));
}

// Yetki: kendi denemesi (viewownresult) ya da rapor görme yetkisi.
$isowner = ((int)$attempt->userid === (int)$USER->id);
if ($isowner) {
    require_capability('mod/optiksinav:viewownresult', $context);
} else {
    require_capability('mod/optiksinav:viewreports', $context);
}

if ($attempt->state !== OPTIKSINAV_STATE_FINISHED) {
    redirect(new moodle_url('/mod/optiksinav/view.php', ['id' => $cm->id]));
}

$PAGE->set_url('/mod/optiksinav/review.php', ['id' => $cm->id, 'attemptid' => $attempt->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$total = $attempt->correctcount + $attempt->wrongcount + $attempt->blankcount;
$duration = $attempt->timefinish - $attempt->timestart;

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name) . ' — ' . get_string('result', 'optiksinav'));

// Özet kutuları.
$cards = [
    ['label' => get_string('correct', 'optiksinav'), 'value' => $attempt->correctcount],
    ['label' => get_string('wrong', 'optiksinav'), 'value' => $attempt->wrongcount],
    ['label' => get_string('blank', 'optiksinav'), 'value' => $attempt->blankcount],
    ['label' => get_string('net', 'optiksinav'), 'value' => format_float($attempt->net, 2)],
    ['label' => get_string('total', 'optiksinav'), 'value' => $total],
    ['label' => get_string('duration', 'optiksinav'), 'value' => format_time($duration)],
];
if ($attempt->grade !== null) {
    $cards[] = ['label' => get_string('grade', 'optiksinav'),
        'value' => format_float($attempt->grade, 2) . ' / ' . format_float($instance->grade, 2)];
}

echo html_writer::start_div('row');
foreach ($cards as $card) {
    echo html_writer::start_div('col-6 col-md-3 mb-3');
    echo html_writer::start_div('card');
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('div', $card['label'], ['class' => 'text-muted small']);
    echo html_writer::tag('div', $card['value'], ['class' => 'h4 mb-0']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

// Cevap dökümü: öğrencinin işareti vs doğru (öğretmene her zaman; öğrenciye her zaman kendi sonucunda).
$key = optiksinav_get_answer_key($instance->id);
$answers = optiksinav_get_attempt_answers($attempt->id);

$table = new html_table();
$table->head = [
    get_string('questionno', 'optiksinav'),
    get_string('youranswer', 'optiksinav'),
    get_string('correctanswer', 'optiksinav'),
    get_string('outcome', 'optiksinav'),
];
$table->data = [];
for ($slot = 1; $slot <= $instance->questioncount; $slot++) {
    $correct = $key[$slot] ?? null;
    $given = $answers[$slot] ?? null;
    if ($correct === null || $correct === '') {
        $outcome = html_writer::tag(
            'span',
            get_string('ungraded', 'optiksinav'),
            ['class' => 'badge bg-light text-dark']
        );
    } else if ($given === null || $given === '') {
        $outcome = html_writer::tag(
            'span',
            get_string('blank', 'optiksinav'),
            ['class' => 'badge bg-secondary']
        );
    } else if (strtoupper($given) === strtoupper($correct)) {
        $outcome = html_writer::tag(
            'span',
            get_string('correct', 'optiksinav'),
            ['class' => 'badge bg-success']
        );
    } else {
        $outcome = html_writer::tag(
            'span',
            get_string('wrong', 'optiksinav'),
            ['class' => 'badge bg-danger']
        );
    }
    $table->data[] = [$slot, $given !== null && $given !== '' ? $given : '—',
        $correct !== null && $correct !== '' ? $correct : '—', $outcome];
}
echo html_writer::tag('h4', get_string('answerbreakdown', 'optiksinav'), ['class' => 'mt-4']);
echo html_writer::table($table);

// Çözüm kitapçığı (varsa ve erişim izni varsa).
$answerurl = optiksinav_get_file_url($context, OPTIKSINAV_FILEAREA_ANSWER);
if ($answerurl) {
    echo html_writer::tag('h4', get_string('answerpdf', 'optiksinav'), ['class' => 'mt-4']);
    echo html_writer::tag('iframe', '', [
        'src' => $answerurl->out(false) . '#toolbar=0',
        'style' => 'width:100%;height:70vh;border:1px solid #ccc;',
        'title' => get_string('answerpdf', 'optiksinav'),
    ]);
}

echo $OUTPUT->footer();
