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
$string['optiksinav:editanswerkey'] = 'Cevap anahtarını düzenle';
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

// PDF yüklemeleri.
$string['pdfheader'] = 'Sınav kitapçıkları (PDF)';
$string['exampdf'] = 'Soru kitapçığı (PDF)';
$string['exampdf_help'] = 'Sınav sırasında öğrenciye gösterilen PDF. Tek PDF dosyası.';
$string['answerpdf'] = 'Çözüm kitapçığı (PDF)';
$string['answerpdf_help'] = 'Çözüm PDF\'i; yalnızca öğretmenlere (ve ileriki bir fazda sınavı bitiren öğrenciye) gösterilir.';

// Doğrulama.
$string['err_questioncount'] = 'Soru sayısı negatif olamaz.';
$string['err_optioncount'] = 'Seçenek sayısı 2 ile 12 arasında olmalı.';
$string['err_penaltyratio'] = 'Ceza oranı negatif olamaz.';

// Cevap anahtarı.
$string['answerkey'] = 'Cevap anahtarı';
$string['answerkey_question'] = '{$a}. soru';
$string['answerkey_unset'] = '—';
$string['answerkey_noquestions'] = 'Önce sınav ayarlarında soru sayısını belirleyin.';
$string['answerkey_saved'] = 'Cevap anahtarı kaydedildi.';
$string['editanswerkey'] = 'Cevap anahtarını düzenle';

// Görüntüleme sayfası.
$string['teacherpanel'] = 'Hazırlık durumu';
$string['status_exampdf'] = 'Soru kitapçığı yüklendi';
$string['status_answerpdf'] = 'Çözüm kitapçığı yüklendi';
$string['status_answerkey'] = 'Cevap anahtarı tamam ({$a->filled}/{$a->total})';
$string['preview_exampdf'] = 'Soru kitapçığını önizle';
$string['preview_answerpdf'] = 'Çözüm kitapçığını önizle';
$string['attempt_comingsoon'] = 'Sınav alma deneyimi yakında etkin olacak.';

// Deneme / sınava girme.
$string['startattempt'] = 'Sınavı başlat';
$string['resumeattempt'] = 'Sınava devam et';
$string['finishattempt'] = 'Sınavı bitir';
$string['viewresult'] = 'Sonucu gör';
$string['confirmfinish'] = 'Sınavı bitirip teslim etmek istediğinize emin misiniz? Bu geri alınamaz.';
$string['alreadyfinished'] = 'Bu sınavı zaten tamamladınız.';
$string['attemptsubmitted'] = 'Sınavınız teslim edildi.';
$string['timeexpired'] = 'Süre doldu — sınavınız otomatik olarak teslim edildi.';
$string['notopen'] = 'Bu sınav şu anda girişe açık değil.';
$string['notready'] = 'Bu sınav henüz hazır değil (kitapçık veya sorular eksik).';
$string['noquestionsyet'] = 'Bu sınavda tanımlı soru yok.';
$string['noexampdf'] = 'Bu sınav için soru kitapçığı yüklenmemiş.';
$string['answeredcount'] = 'Cevaplanan: {$a}';
$string['blankcount2'] = 'Boş: {$a}';
$string['invalidaccess'] = 'Bu denemeye geçersiz erişim.';

// Sonuç / inceleme.
$string['result'] = 'Sonuç';
$string['correct'] = 'Doğru';
$string['wrong'] = 'Yanlış';
$string['blank'] = 'Boş';
$string['net'] = 'Net';
$string['total'] = 'Toplam';
$string['duration'] = 'Süre';
$string['grade'] = 'Not';
$string['answerbreakdown'] = 'Cevap dökümü';
$string['questionno'] = 'No.';
$string['youranswer'] = 'Cevabınız';
$string['correctanswer'] = 'Doğru cevap';
$string['outcome'] = 'Sonuç';
$string['ungraded'] = 'Puanlanmadı';

// Genel.
$string['modulename_help'] = 'Optik Sınav aktivitesi, bir PDF soru kitapçığını A–E optik cevap formuyla birlikte sunar; süreyi sunucu tarafında zorlar, net puanı (doğru − yanlış × ceza) hesaplar ve not defterine işler.';
$string['privacy:metadata'] = 'Optik Sınav eklentisi her kullanıcı için sınav denemelerini ve cevaplarını saklar.';
