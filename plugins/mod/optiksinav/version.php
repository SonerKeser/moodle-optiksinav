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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_optiksinav';   // Full name of the plugin.
$plugin->version   = 2026060906;         // YYYYMMDDXX – bump on every schema/behaviour change.
$plugin->requires  = 2024100700;         // Moodle 4.5.0 LTS.
$plugin->maturity  = MATURITY_BETA;      // Faz 6: testler + kalite + paketleme.
$plugin->release   = 'v1.0.0-beta';
