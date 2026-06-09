<?php
// This file is part of the OptikSınav central manager for Moodle.
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
 * Version metadata for local_optiksinav (cross-course exam centre).
 *
 * @package     local_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_optiksinav';
$plugin->version   = 2026060901;
$plugin->requires  = 2024100700;          // Moodle 4.5.0 LTS.
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = 'v1.0.0-beta';
$plugin->dependencies = [
    'mod_optiksinav' => 2026060903, // Çekirdek aktivite eklentisi.
];
