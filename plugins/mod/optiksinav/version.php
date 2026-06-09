<?php
// This file is part of the OptikSınav activity module for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_optiksinav';   // Full name of the plugin.
$plugin->version   = 2026060901;         // YYYYMMDDXX – bump on every schema/behaviour change.
$plugin->requires  = 2024100700;         // Moodle 4.5.0 LTS.
$plugin->maturity  = MATURITY_ALPHA;     // Faz 1: veri modeli + sınav oluşturma.
$plugin->release   = 'v0.2.0-faz1';
