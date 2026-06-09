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

// Attempt / taking the exam.
$string['startattempt'] = 'Start exam';
$string['resumeattempt'] = 'Resume exam';
$string['finishattempt'] = 'Finish exam';
$string['viewresult'] = 'View result';
$string['confirmfinish'] = 'Are you sure you want to finish and submit the exam? This cannot be undone.';
$string['alreadyfinished'] = 'You have already completed this exam.';
$string['attemptsubmitted'] = 'Your exam has been submitted.';
$string['timeexpired'] = 'Time is up — your exam was submitted automatically.';
$string['notopen'] = 'This exam is not open for attempts right now.';
$string['notready'] = 'This exam is not ready yet (missing booklet or questions).';
$string['noquestionsyet'] = 'This exam has no questions configured.';
$string['noexampdf'] = 'No question booklet has been uploaded for this exam.';
$string['answeredcount'] = 'Answered: {$a}';
$string['blankcount2'] = 'Blank: {$a}';
$string['invalidaccess'] = 'Invalid access to this attempt.';

// Result / review.
$string['result'] = 'Result';
$string['correct'] = 'Correct';
$string['wrong'] = 'Wrong';
$string['blank'] = 'Blank';
$string['net'] = 'Net';
$string['total'] = 'Total';
$string['duration'] = 'Duration';
$string['grade'] = 'Grade';
$string['answerbreakdown'] = 'Answer breakdown';
$string['questionno'] = 'No.';
$string['youranswer'] = 'Your answer';
$string['correctanswer'] = 'Correct answer';
$string['outcome'] = 'Outcome';
$string['ungraded'] = 'Not graded';

// Report.
$string['student'] = 'Student';
$string['viewreport'] = 'View report';
$string['downloadcsv'] = 'Download CSV';
$string['noattempts'] = 'No attempts yet.';
$string['state'] = 'State';
$string['state_inprogress'] = 'In progress';
$string['state_finished'] = 'Finished';
$string['state_abandoned'] = 'Abandoned';

// Misc.
$string['modulename_help'] = 'The Optical exam activity presents a PDF question booklet alongside an A–E optical answer sheet, enforces the time limit on the server, computes a net score (correct − wrong × penalty) and feeds it to the gradebook.';
$string['privacy:metadata:optiksinav_attempt'] = 'Exam attempts made by the user.';
$string['privacy:metadata:optiksinav_attempt:userid'] = 'The user who made the attempt.';
$string['privacy:metadata:optiksinav_attempt:attemptnumber'] = 'The sequence number of the attempt.';
$string['privacy:metadata:optiksinav_attempt:state'] = 'The state of the attempt (in progress / finished).';
$string['privacy:metadata:optiksinav_attempt:timestart'] = 'When the attempt started.';
$string['privacy:metadata:optiksinav_attempt:timefinish'] = 'When the attempt finished.';
$string['privacy:metadata:optiksinav_attempt:correctcount'] = 'Number of correct answers.';
$string['privacy:metadata:optiksinav_attempt:wrongcount'] = 'Number of wrong answers.';
$string['privacy:metadata:optiksinav_attempt:blankcount'] = 'Number of blank answers.';
$string['privacy:metadata:optiksinav_attempt:net'] = 'The net score.';
$string['privacy:metadata:optiksinav_attempt:grade'] = 'The grade for the attempt.';
$string['privacy:metadata:optiksinav_attempt_answer'] = 'The individual answers given in an attempt.';
$string['privacy:metadata:optiksinav_attempt_answer:slot'] = 'The question number.';
$string['privacy:metadata:optiksinav_attempt_answer:answer'] = 'The chosen option for the question.';
