<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Administrator interface to exclude and restore teachers.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

use core\output\notification;
use local_teacher\directory;

require_login();
$context = context_system::instance();
require_capability("moodle/site:config", $context);
admin_externalpage_setup("local_teacher_exclusions");

$action = optional_param("action", "", PARAM_ALPHA);
if (data_submitted() && ($action === "exclude" || $action === "include")) {
    require_sesskey();
    $userid = required_param("userid", PARAM_INT);
    if ($action === "exclude") {
        directory::exclude($userid);
    } else {
        directory::include($userid);
    }
    redirect(
        new moodle_url("/local/teacher/exclusions.php"),
        get_string("exclusionssaved", "local_teacher"),
        null,
        notification::NOTIFY_SUCCESS
    );
}

$search = trim(optional_param("q", "", PARAM_TEXT));
$search = core_text::substr($search, 0, 80);
$users = $search !== "" ? directory::get_teachers($search, 0, 30) : [];
$results = [];
foreach ($users as $user) {
    $results[] = ["id" => $user->id, "fullname" => fullname($user)];
}
$excluded = [];
foreach (directory::excluded() as $user) {
    $excluded[] = ["id" => $user->id, "fullname" => fullname($user)];
}

$data = [
    "heading" => get_string("exclusions", "local_teacher"),
    "instructions" => get_string("exclusionsdesc", "local_teacher"),
    "searchlabel" => get_string("searchteachers", "local_teacher"),
    "searchaction" => (new moodle_url("/local/teacher/exclusions.php"))->out(false),
    "postaction" => (new moodle_url("/local/teacher/exclusions.php"))->out(false),
    "search" => $search,
    "haveresults" => !empty($results),
    "results" => $results,
    "excluded" => $excluded,
    "haveexcluded" => !empty($excluded),
    "excludedtitle" => get_string("excludedteachers", "local_teacher"),
    "noexcluded" => get_string("noexcluded", "local_teacher"),
    "excludelabel" => get_string("excludeteacher", "local_teacher"),
    "includelabel" => get_string("includeteacher", "local_teacher"),
    "searchbutton" => get_string("search"),
    "sesskey" => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_teacher/exclusions", $data);
echo $OUTPUT->footer();
