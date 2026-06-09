<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * English strings for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Optical exam';
$string['modulename'] = 'Optical exam';
$string['modulenameplural'] = 'Optical exams';
$string['pluginadministration'] = 'Optical exam administration';
$string['optiksinav:view'] = 'View optical exam';
$string['optiksinav:attempt'] = 'Attempt optical exam';
$string['optiksinav:viewownresult'] = 'View own result';
$string['optiksinav:viewreports'] = 'View all results and reports';
$string['optiksinav:editanswerkey'] = 'Edit the answer key';
$string['optiksinav:addinstance'] = 'Add a new optical exam';

// Form.
$string['optiksinavname'] = 'Exam name';
$string['optiksinavname_help'] = 'The name shown for this exam in the course.';
$string['timelimit'] = 'Time limit';
$string['timelimit_help'] = 'How long a student has once they start. 0 means no limit.';
$string['timeopen'] = 'Open the exam';
$string['timeclose'] = 'Close the exam';
$string['questioncount'] = 'Number of questions';
$string['optioncount'] = 'Number of options';
$string['optioncount_help'] = 'How many choices each question has (e.g. 5 for A–E).';
$string['penaltyratio'] = 'Wrong-answer penalty ratio';
$string['penaltyratio_help'] = 'Net score = correct − (wrong × ratio). 0.25 means 4 wrong answers cancel 1 correct.';

// PDF uploads.
$string['pdfheader'] = 'Exam booklets (PDF)';
$string['exampdf'] = 'Question booklet (PDF)';
$string['exampdf_help'] = 'The PDF shown to students during the exam. One PDF file.';
$string['answerpdf'] = 'Solution booklet (PDF)';
$string['answerpdf_help'] = 'The solutions PDF, shown only to teachers (and to students after they finish, in a later phase).';

// Validation.
$string['err_questioncount'] = 'The number of questions cannot be negative.';
$string['err_optioncount'] = 'The number of options must be between 2 and 12.';
$string['err_penaltyratio'] = 'The penalty ratio cannot be negative.';

// Answer key.
$string['answerkey'] = 'Answer key';
$string['answerkey_question'] = 'Question {$a}';
$string['answerkey_unset'] = '—';
$string['answerkey_noquestions'] = 'Set the number of questions in the exam settings first.';
$string['answerkey_saved'] = 'Answer key saved.';
$string['editanswerkey'] = 'Edit answer key';

// View page.
$string['teacherpanel'] = 'Setup status';
$string['status_exampdf'] = 'Question booklet uploaded';
$string['status_answerpdf'] = 'Solution booklet uploaded';
$string['status_answerkey'] = 'Answer key complete ({$a->filled}/{$a->total})';
$string['preview_exampdf'] = 'Preview question booklet';
$string['preview_answerpdf'] = 'Preview solution booklet';
$string['attempt_comingsoon'] = 'The exam-taking experience will be available soon.';

// Misc.
$string['modulename_help'] = 'The Optical exam activity presents a PDF question booklet alongside an A–E optical answer sheet, enforces the time limit on the server, computes a net score (correct − wrong × penalty) and feeds it to the gradebook.';
$string['privacy:metadata'] = 'The Optical exam plugin stores exam attempts and answers for each user.';
