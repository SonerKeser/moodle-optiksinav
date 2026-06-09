<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main mod_optiksinav configuration form.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/optiksinav/locallib.php');

/**
 * Activity settings form shown when adding/editing an Optical exam.
 */
class mod_optiksinav_mod_form extends moodleform_mod {
    /**
     * File manager options shared by both PDF upload areas: a single PDF, no subfolders.
     *
     * @return array Options array for the filemanager element / file_prepare_draft_area.
     */
    private function pdf_filemanager_options(): array {
        return ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.pdf']];
    }

    /**
     * Define the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        // --- Genel ---
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('optiksinavname', 'optiksinav'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'optiksinavname', 'optiksinav');

        $this->standard_intro_elements();

        // --- Sınav ayarları ---
        $mform->addElement('header', 'examsettings', get_string('modulename', 'optiksinav'));

        $mform->addElement(
            'duration',
            'timelimit',
            get_string('timelimit', 'optiksinav'),
            ['optional' => true]
        );
        $mform->setDefault('timelimit', 0);
        $mform->addHelpButton('timelimit', 'timelimit', 'optiksinav');

        $mform->addElement(
            'date_time_selector',
            'timeopen',
            get_string('timeopen', 'optiksinav'),
            ['optional' => true]
        );
        $mform->addElement(
            'date_time_selector',
            'timeclose',
            get_string('timeclose', 'optiksinav'),
            ['optional' => true]
        );

        $mform->addElement('text', 'questioncount', get_string('questioncount', 'optiksinav'), ['size' => '5']);
        $mform->setType('questioncount', PARAM_INT);
        $mform->setDefault('questioncount', 0);

        $mform->addElement('text', 'optioncount', get_string('optioncount', 'optiksinav'), ['size' => '5']);
        $mform->setType('optioncount', PARAM_INT);
        $mform->setDefault('optioncount', 5);
        $mform->addHelpButton('optioncount', 'optioncount', 'optiksinav');

        $mform->addElement('text', 'penaltyratio', get_string('penaltyratio', 'optiksinav'), ['size' => '5']);
        $mform->setType('penaltyratio', PARAM_FLOAT);
        $mform->setDefault('penaltyratio', 0.25);
        $mform->addHelpButton('penaltyratio', 'penaltyratio', 'optiksinav');

        // --- PDF kitapçıkları (Moodle File API) ---
        $mform->addElement('header', 'pdfsettings', get_string('pdfheader', 'optiksinav'));

        $mform->addElement(
            'filemanager',
            'exampdf',
            get_string('exampdf', 'optiksinav'),
            null,
            $this->pdf_filemanager_options()
        );
        $mform->addHelpButton('exampdf', 'exampdf', 'optiksinav');

        $mform->addElement(
            'filemanager',
            'answerpdf',
            get_string('answerpdf', 'optiksinav'),
            null,
            $this->pdf_filemanager_options()
        );
        $mform->addHelpButton('answerpdf', 'answerpdf', 'optiksinav');

        // --- Güvenlik ---
        $mform->addElement('header', 'securityheader', get_string('securityheader', 'optiksinav'));

        $mform->addElement(
            'advcheckbox',
            'requireseb',
            get_string('requireseb', 'optiksinav'),
            get_string('requireseb_label', 'optiksinav')
        );
        $mform->setDefault('requireseb', 0);
        $mform->addHelpButton('requireseb', 'requireseb', 'optiksinav');

        // --- Standart Moodle alanları (not, görünürlük, vb.) ---
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Pre-fill the PDF filemanager draft areas with any already-stored files when editing.
     *
     * @param array $defaultvalues Reference to the form default values.
     */
    public function data_preprocessing(&$defaultvalues) {
        $options = $this->pdf_filemanager_options();

        $examdraft = file_get_submitted_draft_itemid('exampdf');
        file_prepare_draft_area(
            $examdraft,
            $this->context->id,
            'mod_optiksinav',
            OPTIKSINAV_FILEAREA_EXAM,
            OPTIKSINAV_FILE_ITEMID,
            $options
        );
        $defaultvalues['exampdf'] = $examdraft;

        $answerdraft = file_get_submitted_draft_itemid('answerpdf');
        file_prepare_draft_area(
            $answerdraft,
            $this->context->id,
            'mod_optiksinav',
            OPTIKSINAV_FILEAREA_ANSWER,
            OPTIKSINAV_FILE_ITEMID,
            $options
        );
        $defaultvalues['answerpdf'] = $answerdraft;
    }

    /**
     * Validate the exam settings.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors keyed by element name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['questioncount'] < 0) {
            $errors['questioncount'] = get_string('err_questioncount', 'optiksinav');
        }
        if ($data['optioncount'] < 2 || $data['optioncount'] > 12) {
            $errors['optioncount'] = get_string('err_optioncount', 'optiksinav');
        }
        if ($data['penaltyratio'] < 0) {
            $errors['penaltyratio'] = get_string('err_penaltyratio', 'optiksinav');
        }

        return $errors;
    }
}
