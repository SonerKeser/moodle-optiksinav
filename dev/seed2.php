<?php
// Dev-only CLI: build a fully-configured exam + a student, for end-to-end attempt testing.
// Run inside the moodle container as daemon:  php /opt/bitnami/moodle/dev_seed2.php

define('CLI_SCRIPT', true);
require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/mod/optiksinav/locallib.php');

global $DB, $CFG;

// 1) Ders.
$course = create_course((object)[
    'fullname' => 'OptikSınav E2E',
    'shortname' => 'OPTIKE2E-'.time(),
    'category' => 1,
    'format' => 'topics',
    'numsections' => 3,
]);
cli_writeln('course='.$course->id);

// 2) Aktivite (5 soru, A-E).
$moduleinfo = (object)[
    'modulename' => 'optiksinav',
    'module' => $DB->get_field('modules', 'id', ['name' => 'optiksinav']),
    'course' => $course->id, 'section' => 1, 'visible' => 1, 'visibleoncoursepage' => 1,
    'cmidnumber' => '', 'groupmode' => 0, 'groupingid' => 0, 'completion' => COMPLETION_TRACKING_NONE,
    'name' => 'E2E Sınavı', 'intro' => '', 'introformat' => FORMAT_HTML,
    'timelimit' => 3600, 'timeopen' => 0, 'timeclose' => 0,
    'questioncount' => 5, 'optioncount' => 5, 'penaltyratio' => 0.25, 'grade' => 100,
];
$moduleinfo = add_moduleinfo($moduleinfo, $course);
$cmid = $moduleinfo->coursemodule;
$instanceid = $moduleinfo->instance;
cli_writeln('cmid='.$cmid.' instanceid='.$instanceid);

// 3) Cevap anahtarı: 1=A 2=B 3=C 4=D 5=E.
optiksinav_save_answer_key($instanceid, 5, [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E']);

// 4) PDF'leri File API ile yükle (minimal geçerli PDF).
$pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
     . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
$context = context_module::instance($cmid);
$fs = get_file_storage();
foreach (['exampdf' => 'soru.pdf', 'answerpdf' => 'cozum.pdf'] as $area => $fname) {
    $fs->delete_area_files($context->id, 'mod_optiksinav', $area, 0);
    $fs->create_file_from_string([
        'contextid' => $context->id, 'component' => 'mod_optiksinav', 'filearea' => $area,
        'itemid' => 0, 'filepath' => '/', 'filename' => $fname,
    ], $pdf);
}
cli_writeln('pdfs_uploaded');

// 5) Öğrenci kullanıcı + derse kayıt.
$username = 'ogrenci_'.$course->id;
if (!$student = $DB->get_record('user', ['username' => $username])) {
    $student = (object)[
        'username' => $username, 'auth' => 'manual', 'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id, 'email' => $username.'@example.com',
        'firstname' => 'Ogrenci', 'lastname' => 'E2E',
    ];
    $student->id = user_create_user($student, false, false);
    $student = $DB->get_record('user', ['id' => $student->id]);
}
update_internal_user_password($student, 'Ogrenci123!');

$studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
$enrolplugin = enrol_get_plugin('manual');
$enrolinstance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
$enrolplugin->enrol_user($enrolinstance, $student->id, $studentrole->id);
cli_writeln('student='.$student->username.' password=Ogrenci123! userid='.$student->id);

cli_writeln('SEED2_OK cmid='.$cmid.' student='.$username);
