<?php
// This file is part of the OptikSınav central manager for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Adds the exam centre to Site administration → Reports.
 *
 * @package     local_optiksinav
 * @copyright   2026 OptikSınav
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('local/optiksinav:viewall', context_system::instance())) {
    $ADMIN->add('reports', new admin_externalpage(
        'local_optiksinav',
        get_string('pluginname', 'local_optiksinav'),
        new moodle_url('/local/optiksinav/index.php'),
        'local/optiksinav:viewall'
    ));
}
