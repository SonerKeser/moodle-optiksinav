<?php
// Dev-only CLI seed script: creates a course + an optiksinav activity + answer key
// so the authoring flow can be exercised end-to-end. NOT part of the shipped plugin.
// Usage (inside the moodle container, as daemon):
//   php /opt/bitnami/moodle/dev_seed.php

define('CLI_SCRIPT', true);
require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->dirroot.'/mod/optiksinav/locallib.php');

global $DB;

// 1) Ders oluştur (Çeşitli kategorisi = id 1).
$course = create_course((object)[
    'fullname' => 'OptikSınav Test Dersi',
    'shortname' => 'OPTIKTEST-'.time(),
    'category' => 1,
    'format' => 'topics',
    'numsections' => 3,
]);
cli_writeln('Ders oluşturuldu: id='.$course->id);

// 2) optiksinav aktivitesi ekle (gerçek UI API'si: add_moduleinfo).
$moduleinfo = (object)[
    'modulename' => 'optiksinav',
    'module' => $DB->get_field('modules', 'id', ['name' => 'optiksinav']),
    'course' => $course->id,
    'section' => 1,
    'visible' => 1,
    'visibleoncoursepage' => 1,
    'cmidnumber' => '',
    'groupmode' => 0,
    'groupingid' => 0,
    'completion' => COMPLETION_TRACKING_NONE,
    // Modül alanları:
    'name' => 'Deneme Sınavı 1',
    'intro' => '<p>Bu bir test sınavıdır.</p>',
    'introformat' => FORMAT_HTML,
    'timelimit' => 3600,
    'timeopen' => 0,
    'timeclose' => 0,
    'questioncount' => 5,
    'optioncount' => 5,
    'penaltyratio' => 0.25,
    'grade' => 100,
];
$moduleinfo = add_moduleinfo($moduleinfo, $course);
$cmid = $moduleinfo->coursemodule;
$instanceid = $moduleinfo->instance;
cli_writeln('Aktivite oluşturuldu: cmid='.$cmid.' instanceid='.$instanceid);

// 3) Cevap anahtarını kaydet (5 soru: A,B,C,D,E) ve geri oku.
optiksinav_save_answer_key($instanceid, 5, [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E']);
$key = optiksinav_get_answer_key($instanceid);
cli_writeln('Cevap anahtarı kaydedildi: '.json_encode($key));
cli_writeln('Dolu slot sayısı: '.optiksinav_answer_key_filled_count($instanceid).'/5');

// Bir slotu boşalt + soru sayısını 3'e düşürerek orphan temizliğini test et.
optiksinav_save_answer_key($instanceid, 3, [1 => 'E', 2 => '', 3 => 'C']);
$key2 = optiksinav_get_answer_key($instanceid);
cli_writeln('Güncellenmiş anahtar (3 slot): '.json_encode($key2));
cli_writeln('Toplam soru satırı (orphan kalmamalı=3): '.$DB->count_records('optiksinav_question', ['optiksinavid' => $instanceid]));

cli_writeln('SEED_OK cmid='.$cmid);
