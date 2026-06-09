<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Teacher report: all student attempts for one exam, with CSV export.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$id = required_param('id', PARAM_INT);                  // Course module id.
$download = optional_param('download', '', PARAM_ALPHA); // '' or 'csv'.

$cm = get_coursemodule_from_id('optiksinav', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('optiksinav', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/optiksinav:viewreports', $context);

// Tüm bitmiş/devam eden denemeler + kullanıcı.
$userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
$sql = "SELECT a.*, $userfields, u.id AS userid2
          FROM {optiksinav_attempt} a
          JOIN {user} u ON u.id = a.userid
         WHERE a.optiksinavid = :oid
      ORDER BY u.lastname, u.firstname, a.attemptnumber";
$rows = $DB->get_records_sql($sql, ['oid' => $instance->id]);

// Tablo verisini hazırla.
$columns = [
    'student' => get_string('student', 'optiksinav'),
    'state' => get_string('state', 'optiksinav'),
    'correct' => get_string('correct', 'optiksinav'),
    'wrong' => get_string('wrong', 'optiksinav'),
    'blank' => get_string('blank', 'optiksinav'),
    'net' => get_string('net', 'optiksinav'),
    'grade' => get_string('grade', 'optiksinav'),
    'duration' => get_string('duration', 'optiksinav'),
    'focusloss' => get_string('focusloss', 'optiksinav'),
];

$data = [];
foreach ($rows as $r) {
    $duration = ($r->state === OPTIKSINAV_STATE_FINISHED && $r->timefinish)
        ? format_time($r->timefinish - $r->timestart) : '';
    $data[] = (object)[
        'student' => fullname($r),
        'state' => get_string('state_'.$r->state, 'optiksinav'),
        'correct' => $r->correctcount,
        'wrong' => $r->wrongcount,
        'blank' => $r->blankcount,
        'net' => format_float($r->net, 2),
        'grade' => $r->grade === null ? '' : format_float($r->grade, 2),
        'duration' => $duration,
        'focusloss' => $r->focuslosscount,
        '_attemptid' => $r->id,
        '_state' => $r->state,
    ];
}

// CSV dışa aktarma.
if ($download === 'csv') {
    // Bazı barındırmalarda php.ini output_buffering açık olur; download_data() tampon istemez.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    \core\dataformat::download_data(
        clean_filename('optiksinav_'.$instance->name),
        'csv',
        $columns,
        array_map(function($row) {
            unset($row->_attemptid, $row->_state);
            return $row;
        }, $data)
    );
    die();
}

$PAGE->set_url('/mod/optiksinav/report.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name).' — '.get_string('report', 'optiksinav'));

if (empty($data)) {
    echo $OUTPUT->notification(get_string('noattempts', 'optiksinav'),
        \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    die();
}

// Dışa aktar butonu.
$csvurl = new moodle_url('/mod/optiksinav/report.php', ['id' => $cm->id, 'download' => 'csv']);
echo html_writer::div($OUTPUT->single_button($csvurl, get_string('downloadcsv', 'optiksinav'), 'get'),
    'mb-3');

$table = new html_table();
$table->head = array_values($columns);
$table->head[] = '';
$table->attributes['class'] = 'generaltable';
foreach ($data as $row) {
    $cells = [$row->student, $row->state, $row->correct, $row->wrong, $row->blank,
        $row->net, $row->grade, $row->duration, $row->focusloss];
    if ($row->_state === OPTIKSINAV_STATE_FINISHED) {
        $reviewurl = new moodle_url('/mod/optiksinav/review.php',
            ['id' => $cm->id, 'attemptid' => $row->_attemptid]);
        $cells[] = html_writer::link($reviewurl, get_string('viewresult', 'optiksinav'));
    } else {
        $cells[] = '';
    }
    $table->data[] = $cells;
}
echo html_writer::table($table);

echo $OUTPUT->footer();
