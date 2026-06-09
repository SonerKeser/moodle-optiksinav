<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Library of interface functions and constants for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Declare which optional features the module supports.
 *
 * @param string $feature FEATURE_xxx constant.
 * @return mixed True/false for supported features, null for unknown.
 */
function optiksinav_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Create a new exam instance.
 *
 * @param stdClass $data Form data from mod_form.
 * @param mod_optiksinav_mod_form|null $mform The form instance.
 * @return int The id of the newly inserted record.
 */
function optiksinav_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    $data->intro = $data->intro ?? '';
    $data->introformat = $data->introformat ?? FORMAT_HTML;

    $data->id = $DB->insert_record('optiksinav', $data);

    optiksinav_save_pdf_files($data);
    optiksinav_grade_item_update($data);

    return $data->id;
}

/**
 * Update an existing exam instance.
 *
 * @param stdClass $data Form data from mod_form (contains instance id in $data->instance).
 * @param mod_optiksinav_mod_form|null $mform The form instance.
 * @return bool True on success.
 */
function optiksinav_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $result = $DB->update_record('optiksinav', $data);

    optiksinav_save_pdf_files($data);
    optiksinav_grade_item_update($data);
    optiksinav_update_grades($data);

    return $result;
}

/**
 * Delete an exam instance and all associated data.
 *
 * @param int $id The instance id.
 * @return bool True on success.
 */
function optiksinav_delete_instance($id) {
    global $DB;

    $instance = $DB->get_record('optiksinav', ['id' => $id]);
    if (!$instance) {
        return false;
    }

    // Not defteri grade item'ı temizle.
    optiksinav_grade_item_delete($instance);

    // İlişkili denemelerin cevapları → denemeler → cevap anahtarı → örnek.
    $attemptids = $DB->get_fieldset_select('optiksinav_attempt', 'id', 'optiksinavid = ?', [$id]);
    if ($attemptids) {
        $DB->delete_records_list('optiksinav_attempt_answer', 'attemptid', $attemptids);
    }
    $DB->delete_records('optiksinav_attempt', ['optiksinavid' => $id]);
    $DB->delete_records('optiksinav_question', ['optiksinavid' => $id]);
    $DB->delete_records('optiksinav', ['id' => $id]);

    // Yüklenen PDF'ler aktivite bağlamı silinince Moodle tarafından otomatik temizlenir;
    // yine de garanti için bağlam dosyalarını burada da silmeye gerek yoktur (cm henüz mevcut değil).

    return true;
}

/**
 * Save the submitted PDF draft areas into this instance's permanent file areas.
 *
 * @param stdClass $data Form data; must contain coursemodule, id and the draft itemids.
 * @return void
 */
function optiksinav_save_pdf_files(stdClass $data): void {
    require_once(__DIR__.'/locallib.php');

    if (empty($data->coursemodule)) {
        return;
    }
    $context = context_module::instance($data->coursemodule);
    $options = ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']];

    if (isset($data->exampdf)) {
        file_save_draft_area_files($data->exampdf, $context->id, 'mod_optiksinav',
            OPTIKSINAV_FILEAREA_EXAM, OPTIKSINAV_FILE_ITEMID, $options);
    }
    if (isset($data->answerpdf)) {
        file_save_draft_area_files($data->answerpdf, $context->id, 'mod_optiksinav',
            OPTIKSINAV_FILEAREA_ANSWER, OPTIKSINAV_FILE_ITEMID, $options);
    }
}

/**
 * Serve files from the mod_optiksinav file areas with capability checks.
 *
 * The exam booklet is visible to anyone who can view the activity.
 * The answer (solution) booklet is restricted to users with the viewreports
 * capability (teachers/managers), plus students who have finished their own attempt.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path, filename).
 * @param bool $forcedownload Whether or not to force download.
 * @param array $options Additional options affecting file serving.
 * @return bool False if the file was not found; otherwise serves and exits.
 */
function optiksinav_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_once(__DIR__.'/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if (!in_array($filearea, [OPTIKSINAV_FILEAREA_EXAM, OPTIKSINAV_FILEAREA_ANSWER], true)) {
        return false;
    }

    require_capability('mod/optiksinav:view', $context);

    if ($filearea === OPTIKSINAV_FILEAREA_ANSWER) {
        // Çözüm kitapçığı: öğretmen/yönetici her zaman; öğrenci ANCAK kendi denemesi bitince.
        if (!has_capability('mod/optiksinav:viewreports', $context)) {
            global $USER, $DB;
            $instance = $DB->get_record('optiksinav', ['id' => $cm->instance], 'id', MUST_EXIST);
            $hasfinished = $DB->record_exists('optiksinav_attempt', [
                'optiksinavid' => $instance->id,
                'userid' => $USER->id,
                'state' => OPTIKSINAV_STATE_FINISHED,
            ]);
            if (!$hasfinished) {
                send_file_not_found();
            }
        }
    }

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_optiksinav', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    // PDF'i indirtmek yerine tarayıcıda göster (lifetime 0 = önbelleksiz, dev için güvenli).
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Create or update the grade item for an exam instance, optionally pushing grades.
 *
 * @param stdClass $instance The optiksinav instance (must have id, course, name, grade).
 * @param mixed $grades Optional: an array of grade objects keyed by userid, a single
 *                      grade object, or the string 'reset'. Null leaves grades untouched.
 * @return int GRADE_UPDATE_OK / GRADE_UPDATE_FAILED.
 */
function optiksinav_grade_item_update($instance, $grades = null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = [
        'itemname' => clean_param($instance->name, PARAM_NOTAGS),
    ];
    if (isset($instance->grade) && $instance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = $instance->grade;
        $item['grademin'] = 0;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $item['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/optiksinav', $instance->course, 'mod', 'optiksinav',
        $instance->id, 0, $grades, $item);
}

/**
 * Delete the grade item for an exam instance.
 *
 * @param stdClass $instance The optiksinav instance.
 * @return int GRADE_UPDATE_OK / GRADE_UPDATE_FAILED.
 */
function optiksinav_grade_item_delete($instance) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/optiksinav', $instance->course, 'mod', 'optiksinav',
        $instance->id, 0, null, ['deleted' => 1]);
}

/**
 * Return the gradebook grades for an instance as userid => {userid, rawgrade}.
 *
 * The best (highest) finished attempt is used per user.
 *
 * @param stdClass $instance The optiksinav instance.
 * @param int $userid Limit to one user, or 0 for all.
 * @return array<int,stdClass> Grades keyed by user id.
 */
function optiksinav_get_user_grades($instance, $userid = 0) {
    global $DB;

    $params = ['optiksinavid' => $instance->id, 'state' => 'finished'];
    $where = 'optiksinavid = :optiksinavid AND state = :state AND grade IS NOT NULL';
    if ($userid) {
        $where .= ' AND userid = :userid';
        $params['userid'] = $userid;
    }

    $records = $DB->get_records_select('optiksinav_attempt', $where, $params, '', 'id, userid, grade');
    $grades = [];
    foreach ($records as $rec) {
        if (!isset($grades[$rec->userid]) || $rec->grade > $grades[$rec->userid]->rawgrade) {
            $grades[$rec->userid] = (object)['userid' => $rec->userid, 'rawgrade' => $rec->grade];
        }
    }
    return $grades;
}

/**
 * Push grades to the gradebook for an instance (one user or all).
 *
 * @param stdClass $instance The optiksinav instance.
 * @param int $userid Limit to one user, or 0 for all.
 * @param bool $nullifnone When true and a single user has no grade, write a null grade.
 * @return void
 */
function optiksinav_update_grades($instance, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($instance->grade) || $instance->grade == 0) {
        optiksinav_grade_item_update($instance);
    } else if ($grades = optiksinav_get_user_grades($instance, $userid)) {
        optiksinav_grade_item_update($instance, $grades);
    } else if ($userid && $nullifnone) {
        optiksinav_grade_item_update($instance, (object)['userid' => $userid, 'rawgrade' => null]);
    } else {
        optiksinav_grade_item_update($instance);
    }
}
