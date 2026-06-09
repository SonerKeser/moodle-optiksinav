<?php
// Dev-only CLI: back up a course containing an optiksinav activity (with user data),
// restore it into a fresh course, and verify the data survived the round-trip.
// Run inside the moodle container as daemon:  php /opt/bitnami/moodle/dev_backuptest.php <cmid>

define('CLI_SCRIPT', true);
require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

global $DB, $USER;
$USER = get_admin();

$cmid = (int)($argv[1] ?? 0);
$cm = get_coursemodule_from_id('optiksinav', $cmid, 0, false, MUST_EXIST);
$sourcecourseid = $cm->course;
$sourceinstance = $cm->instance;
cli_writeln("source course=$sourcecourseid instance=$sourceinstance");
cli_writeln('source: questions='.$DB->count_records('optiksinav_question', ['optiksinavid' => $sourceinstance])
    .' attempts='.$DB->count_records('optiksinav_attempt', ['optiksinavid' => $sourceinstance]));

// 1) Yedekle (kullanıcı verisiyle).
$bc = new backup_controller(backup::TYPE_1COURSE, $sourcecourseid, backup::FORMAT_MOODLE,
    backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);
$bc->get_plan()->get_setting('users')->set_value(true);
$bc->set_status(backup::STATUS_AWAITING);
$bc->execute_plan();
$results = $bc->get_results();
$file = $results['backup_destination'];
cli_writeln('backup done, file size='.$file->get_filesize());
$bc->destroy();

// 2) Hedef boş ders.
$target = create_course((object)[
    'fullname' => 'RESTORE HEDEF '.time(), 'shortname' => 'RESTORE-'.time(), 'category' => 1,
]);
cli_writeln('target course='.$target->id);

// 3) Yedeği aç ve geri yükle.
$restoreid = restore_controller::get_tempdir_name($target->id, $USER->id);
$path = make_backup_temp_directory($restoreid);
$fp = get_file_packer('application/vnd.moodle.backup');
$file->extract_to_pathname($fp, $path);

$rc = new restore_controller($restoreid, $target->id, backup::INTERACTIVE_NO,
    backup::MODE_GENERAL, $USER->id, backup::TARGET_NEW_COURSE);
$rc->execute_precheck();
$rc->execute_plan();
$rc->destroy();
cli_writeln('restore done');

// 4) Doğrula.
$restored = $DB->get_records_sql(
    "SELECT o.* FROM {optiksinav} o WHERE o.course = ?", [$target->id]);
$ri = reset($restored);
if (!$ri) {
    cli_writeln('FAIL: restored instance yok');
    exit(1);
}
$rcontext = context_module::instance(
    $DB->get_field_sql("SELECT cm.id FROM {course_modules} cm JOIN {modules} m ON m.id=cm.module
        AND m.name='optiksinav' WHERE cm.instance=? AND cm.course=?", [$ri->id, $target->id]));
$fs = get_file_storage();
$exampdf = $fs->get_area_files($rcontext->id, 'mod_optiksinav', 'exampdf', 0, 'id', false);

cli_writeln('RESTORED: name='.$ri->name.' questioncount='.$ri->questioncount
    .' optioncount='.$ri->optioncount);
cli_writeln('RESTORED questions='.$DB->count_records('optiksinav_question', ['optiksinavid' => $ri->id])
    .' attempts='.$DB->count_records('optiksinav_attempt', ['optiksinavid' => $ri->id])
    .' answers='.$DB->count_records_sql("SELECT COUNT(*) FROM {optiksinav_attempt_answer} aa
        JOIN {optiksinav_attempt} a ON a.id=aa.attemptid WHERE a.optiksinavid=?", [$ri->id]));
cli_writeln('RESTORED exampdf files='.count($exampdf));
$key = $DB->get_records_menu('optiksinav_question', ['optiksinavid' => $ri->id], 'slot', 'slot, correctanswer');
cli_writeln('RESTORED answer key='.json_encode($key));
cli_writeln('BACKUPTEST_OK');
