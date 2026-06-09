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
 * Answer-key editing form for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_optiksinav\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/optiksinav/locallib.php');

/**
 * Lets a teacher set the correct option (A–E) for every question slot.
 *
 * Expected custom data:
 *  - cmid:          course module id (int)
 *  - questioncount: number of question slots (int)
 *  - optioncount:   number of options per question (int)
 */
class answerkey_form extends \moodleform {
    /**
     * Build one labelled dropdown per question slot.
     */
    public function definition() {
        $mform = $this->_form;

        $cmid = $this->_customdata['cmid'];
        $questioncount = (int)$this->_customdata['questioncount'];
        $optioncount = (int)$this->_customdata['optioncount'];

        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);

        if ($questioncount < 1) {
            $mform->addElement(
                'static',
                'noquestions',
                '',
                get_string('answerkey_noquestions', 'optiksinav')
            );
            return;
        }

        // Şık seçenekleri: '' (boş) + A, B, C, ...
        $letters = optiksinav_get_option_letters($optioncount);
        $choices = ['' => get_string('answerkey_unset', 'optiksinav')];
        foreach ($letters as $letter) {
            $choices[$letter] = $letter;
        }

        $mform->addElement('header', 'keyheader', get_string('answerkey', 'optiksinav'));

        for ($slot = 1; $slot <= $questioncount; $slot++) {
            $mform->addElement(
                'select',
                "answer[$slot]",
                get_string('answerkey_question', 'optiksinav', $slot),
                $choices
            );
            $mform->setType("answer[$slot]", PARAM_ALPHA);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
