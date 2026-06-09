<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Lists all Optical exam instances in a course.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_login($course);

$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/optiksinav/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'optiksinav'));

$instances = get_all_instances_in_course('optiksinav', $course);

if (empty($instances)) {
    notice(get_string('noinstances', 'moodle', get_string('modulenameplural', 'optiksinav')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('questioncount', 'optiksinav')];
$table->data = [];

foreach ($instances as $instance) {
    $link = new moodle_url('/mod/optiksinav/view.php', ['id' => $instance->coursemodule]);
    $name = html_writer::link($link, format_string($instance->name),
        $instance->visible ? [] : ['class' => 'dimmed']);
    $table->data[] = [$name, $instance->questioncount];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
