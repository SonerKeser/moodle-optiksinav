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
 * Answer-key editor page for a single Optical exam (teacher-facing).
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

use mod_optiksinav\form\answerkey_form;

$id = required_param('id', PARAM_INT); // Course module id.

$cm = get_coursemodule_from_id('optiksinav', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('optiksinav', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/optiksinav:editanswerkey', $context);

$pageurl = new moodle_url('/mod/optiksinav/editkey.php', ['id' => $cm->id]);
$viewurl = new moodle_url('/mod/optiksinav/view.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mform = new answerkey_form($pageurl, [
    'cmid' => $cm->id,
    'questioncount' => $moduleinstance->questioncount,
    'optioncount' => $moduleinstance->optioncount,
]);

if ($mform->is_cancelled()) {
    redirect($viewurl);
}

if ($data = $mform->get_data()) {
    $answers = isset($data->answer) && is_array($data->answer) ? $data->answer : [];
    optiksinav_save_answer_key($moduleinstance->id, $moduleinstance->questioncount, $answers);
    redirect(
        $viewurl,
        get_string('answerkey_saved', 'optiksinav'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Mevcut cevap anahtarını forma yükle.
$existing = optiksinav_get_answer_key($moduleinstance->id);
$mform->set_data(['answer' => $existing]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('answerkey', 'optiksinav') . ': ' . format_string($moduleinstance->name));
$mform->display();
echo $OUTPUT->footer();
