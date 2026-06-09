<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Türkçe diziler — mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Optik Sınav';
$string['modulename'] = 'Optik Sınav';
$string['modulenameplural'] = 'Optik Sınavlar';
$string['pluginadministration'] = 'Optik Sınav yönetimi';
$string['optiksinav:view'] = 'Optik sınavı görüntüle';
$string['optiksinav:attempt'] = 'Optik sınava gir';
$string['optiksinav:viewownresult'] = 'Kendi sonucunu gör';
$string['optiksinav:viewreports'] = 'Tüm sonuçları ve raporları gör';
$string['optiksinav:addinstance'] = 'Yeni optik sınav ekle';

// Form.
$string['optiksinavname'] = 'Sınav adı';
$string['optiksinavname_help'] = 'Bu sınavın derste görünecek adı.';
$string['timelimit'] = 'Süre sınırı';
$string['timelimit_help'] = 'Öğrenci sınava başladıktan sonra sahip olduğu süre. 0 = sınırsız.';
$string['timeopen'] = 'Sınavı aç';
$string['timeclose'] = 'Sınavı kapat';
$string['questioncount'] = 'Soru sayısı';
$string['optioncount'] = 'Seçenek sayısı';
$string['optioncount_help'] = 'Her sorunun kaç seçeneği var (örn. A–E için 5).';
$string['penaltyratio'] = 'Yanlış cevap ceza oranı';
$string['penaltyratio_help'] = 'Net = doğru − (yanlış × oran). 0.25 → 4 yanlış 1 doğruyu götürür.';

// Genel.
$string['modulename_help'] = 'Optik Sınav aktivitesi, bir PDF soru kitapçığını A–E optik cevap formuyla birlikte sunar; süreyi sunucu tarafında zorlar, net puanı (doğru − yanlış × ceza) hesaplar ve not defterine işler.';
$string['privacy:metadata'] = 'Optik Sınav eklentisi her kullanıcı için sınav denemelerini ve cevaplarını saklar.';
