<?php
// This file is part of the OptikSınav central manager for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Version metadata for local_optiksinav (cross-course exam centre).
 *
 * @package     local_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_optiksinav';
$plugin->version   = 2026060900;
$plugin->requires  = 2024100700;          // Moodle 4.5.0 LTS.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'v0.1.0-faz4';
$plugin->dependencies = [
    'mod_optiksinav' => 2026060903,       // Çekirdek aktivite eklentisi.
];
