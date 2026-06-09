<?php
// Dev-only CLI: smoke-test the privacy provider wiring.
// Run inside the moodle container as daemon:  php /opt/bitnami/moodle/dev_privacytest.php <userid>

define('CLI_SCRIPT', true);
require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use mod_optiksinav\privacy\provider;

global $DB;
$userid = (int)($argv[1] ?? 0);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// 1) Metadata.
$collection = provider::get_metadata(new collection('mod_optiksinav'));
cli_writeln('metadata items='.count($collection->get_collection()));

// 2) Contexts for user.
$contextlist = provider::get_contexts_for_userid($userid);
$contexts = $contextlist->get_contexts();
cli_writeln('contexts for user '.$userid.'='.count($contexts));

// 3) Export (throw etmemeli).
$approved = new approved_contextlist($user, 'mod_optiksinav', array_map(function($c) {
    return $c->id;
}, $contexts));
provider::export_user_data($approved);
cli_writeln('export_user_data ran without error');

// 4) Userlist.
$ctx = reset($contexts);
if ($ctx) {
    $userlist = new \core_privacy\local\request\userlist($ctx, 'mod_optiksinav');
    provider::get_users_in_context($userlist);
    cli_writeln('users in first context='.count($userlist->get_userids()));
}

cli_writeln('PRIVACYTEST_OK');
