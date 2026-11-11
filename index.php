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
 * Public directory index with search and pagination.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

use local_teacher\directory;
use local_teacher\presentation;

if (!directory::is_enabled()) {
    throw new moodle_exception("directorydisabled", "local_teacher");
}

if (!directory::allows_guests()) {
    require_login();
}

$context = context_system::instance();
if (isloggedin() && !isguestuser()) {
    require_capability("local/teacher:view", $context);
}

$search = trim(optional_param("q", "", PARAM_TEXT));
$search = core_text::substr($search, 0, 80);
$page = max(0, optional_param("page", 0, PARAM_INT));
$pagesize = 18;

$PAGE->set_url(new moodle_url("/local/teacher/index.php", ["q" => $search, "page" => $page]));
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("teachers", "local_teacher"));
$PAGE->set_heading(get_string("teachers", "local_teacher"));
$PAGE->requires->css(new moodle_url("/local/teacher/styles.css"));

$total = directory::count($search);
$users = directory::get_teachers($search, $page, $pagesize);
$courses = directory::courses_for(array_map("intval", array_keys($users)));
$cards = [];
foreach ($users as $user) {
    $cards[] = presentation::card($user, $courses[$user->id] ?? []);
}

$baseurl = new moodle_url("/local/teacher/index.php", ["q" => $search]);
$pagingbar = new paging_bar($total, $page, $pagesize, $baseurl, "page");

$data = [
    "heading" => get_string("teachers", "local_teacher"),
    "intro" => get_string("directoryintro", "local_teacher"),
    "searchlabel" => get_string("searchteachers", "local_teacher"),
    "search" => $search,
    "searchaction" => (new moodle_url("/local/teacher/index.php"))->out(false),
    "searchbutton" => get_string("search"),
    "countlabel" => get_string("teachercount", "local_teacher", $total),
    "teachers" => $cards,
    "hasteachers" => !empty($cards),
    "emptylabel" => get_string("noteachers", "local_teacher"),
    "paginghtml" => $OUTPUT->render($pagingbar),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_teacher/directory", $data);
echo $OUTPUT->footer();
