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
 * Database upgrade steps for mod_optiksinav.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute the schema upgrades.
 *
 * @param int $oldversion The currently installed plugin version.
 * @return bool Always true on success.
 */
function xmldb_optiksinav_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Faz 1: cevap anahtarı + deneme + deneme-cevabı tabloları.
    if ($oldversion < 2026060901) {
        // optiksinav_question.
        $table = new xmldb_table('optiksinav_question');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('optiksinavid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('slot', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('correctanswer', XMLDB_TYPE_CHAR, '1', null, null, null, null);
            $table->add_field('points', XMLDB_TYPE_NUMBER, '6, 2', null, XMLDB_NOTNULL, null, '1');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('optiksinavid', XMLDB_KEY_FOREIGN, ['optiksinavid'], 'optiksinav', ['id']);
            $table->add_index('optiksinavid-slot', XMLDB_INDEX_UNIQUE, ['optiksinavid', 'slot']);
            $dbman->create_table($table);
        }

        // optiksinav_attempt.
        $table = new xmldb_table('optiksinav_attempt');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('optiksinavid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('attemptnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('state', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'inprogress');
            $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timefinish', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timedue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('correctcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('wrongcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('blankcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('net', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('optiksinavid', XMLDB_KEY_FOREIGN, ['optiksinavid'], 'optiksinav', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('optiksinavid-userid', XMLDB_INDEX_NOTUNIQUE, ['optiksinavid', 'userid']);
            $dbman->create_table($table);
        }

        // optiksinav_attempt_answer.
        $table = new xmldb_table('optiksinav_attempt_answer');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('slot', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('answer', XMLDB_TYPE_CHAR, '1', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('attemptid', XMLDB_KEY_FOREIGN, ['attemptid'], 'optiksinav_attempt', ['id']);
            $table->add_index('attemptid-slot', XMLDB_INDEX_UNIQUE, ['attemptid', 'slot']);
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026060901, 'optiksinav');
    }

    // Faz 5: Safe Exam Browser zorunluluğu + odak-kaybı sayacı.
    if ($oldversion < 2026060905) {
        $table = new xmldb_table('optiksinav');
        $field = new xmldb_field('requireseb', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'grade');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('optiksinav_attempt');
        $field = new xmldb_field('focuslosscount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'blankcount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026060905, 'optiksinav');
    }

    return true;
}
