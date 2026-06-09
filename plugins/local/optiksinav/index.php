<?php
// This file is part of the OptikSınav central manager for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Site-wide exam centre: every OptikSınav activity across all courses.
 *
 * @package     local_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('local_optiksinav');

$PAGE->set_title(get_string('heading', 'local_optiksinav'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading', 'local_optiksinav'));
echo html_writer::tag('p', get_string('intro', 'local_optiksinav'));

// Tüm optiksinav örnekleri + ders + bitmiş deneme istatistikleri.
$sql = "SELECT o.id, o.name, o.questioncount, c.id AS courseid, c.fullname, cm.id AS cmid,
               COUNT(DISTINCT CASE WHEN a.state = :finished THEN a.id END) AS finishedcount,
               AVG(CASE WHEN a.state = :finished2 THEN a.net END) AS avgnet
          FROM {optiksinav} o
          JOIN {course} c ON c.id = o.course
          JOIN {course_modules} cm ON cm.instance = o.id AND cm.course = c.id
          JOIN {modules} m ON m.id = cm.module AND m.name = 'optiksinav'
     LEFT JOIN {optiksinav_attempt} a ON a.optiksinavid = o.id
      GROUP BY o.id, o.name, o.questioncount, c.id, c.fullname, cm.id
      ORDER BY c.fullname, o.name";
$records = $DB->get_records_sql($sql, ['finished' => 'finished', 'finished2' => 'finished']);

if (!$records) {
    echo $OUTPUT->notification(get_string('noexams', 'local_optiksinav'),
        \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    die();
}

$table = new html_table();
$table->head = [
    get_string('course', 'local_optiksinav'),
    get_string('examname', 'local_optiksinav'),
    get_string('questions', 'local_optiksinav'),
    get_string('finishedattempts', 'local_optiksinav'),
    get_string('avgnet', 'local_optiksinav'),
    get_string('actions', 'local_optiksinav'),
];
$table->attributes['class'] = 'generaltable';

foreach ($records as $r) {
    $courseurl = new moodle_url('/course/view.php', ['id' => $r->courseid]);
    $viewurl = new moodle_url('/mod/optiksinav/view.php', ['id' => $r->cmid]);
    $reporturl = new moodle_url('/mod/optiksinav/report.php', ['id' => $r->cmid]);

    $actions = html_writer::link($viewurl, get_string('view')).' · '.
        html_writer::link($reporturl, get_string('report', 'optiksinav'));

    $table->data[] = [
        html_writer::link($courseurl, format_string($r->fullname)),
        html_writer::link($viewurl, format_string($r->name)),
        $r->questioncount,
        (int)$r->finishedcount,
        $r->avgnet !== null ? format_float($r->avgnet, 2) : '—',
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
