<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * The main mod_optiksinav configuration form.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Activity settings form shown when adding/editing an Optical exam.
 */
class mod_optiksinav_mod_form extends moodleform_mod {

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

        $mform->addElement('duration', 'timelimit', get_string('timelimit', 'optiksinav'),
            ['optional' => true]);
        $mform->setDefault('timelimit', 0);
        $mform->addHelpButton('timelimit', 'timelimit', 'optiksinav');

        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'optiksinav'),
            ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'optiksinav'),
            ['optional' => true]);

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

        // --- Standart Moodle alanları (not, görünürlük, vb.) ---
        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
