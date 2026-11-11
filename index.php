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

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Guest access is optional and guarded by the plugin setting.
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
$categoryid = max(0, optional_param("category", 0, PARAM_INT));
$sort = optional_param("sort", "az", PARAM_ALPHA);
$sort = in_array($sort, ["az", "za"], true) ? $sort : "az";
$page = max(0, optional_param("page", 0, PARAM_INT));
$pagesize = 18;

// A public category must contain an eligible teacher in a visible course.
$categories = directory::categories();
if ($categoryid > 0 && !isset($categories[$categoryid])) {
    $categoryid = 0;
}
$categoryoptions = [[
    "id" => 0,
    "name" => get_string("allcategories", "local_teacher"),
    "selected" => $categoryid === 0,
]];
foreach ($categories as $category) {
    $categoryoptions[] = [
        "id" => (int) $category->id,
        "name" => format_string($category->name),
        "selected" => $categoryid === (int) $category->id,
    ];
}
$PAGE->set_url(new moodle_url("/local/teacher/index.php", [
    "q" => $search,
    "category" => $categoryid,
    "sort" => $sort,
    "page" => $page,
]));
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(get_string("teachers", "local_teacher"));
$PAGE->requires->css(new moodle_url("/local/teacher/styles.css"));

$total = directory::count($search, $categoryid);
if ($total > 0) {
    $page = min($page, (int) ceil($total / $pagesize) - 1);
} else {
    $page = 0;
}
$users = directory::get_teachers($search, $page, $pagesize, $categoryid, $sort);
$courses = directory::courses_for(array_map("intval", array_keys($users)));
$cards = [];
foreach ($users as $user) {
    $teachercourses = $courses[$user->id] ?? [];
    if ($categoryid > 0) {
        $teachercourses = array_values(array_filter($teachercourses, static function($course) use ($categoryid): bool {
            return (int) $course->category === $categoryid;
        }));
    }
    $cards[] = presentation::card($user, $teachercourses);
}

$baseurl = new moodle_url("/local/teacher/index.php", [
    "q" => $search,
    "category" => $categoryid,
    "sort" => $sort,
]);
$pagingbar = new paging_bar($total, $page, $pagesize, $baseurl, "page");

$data = [
    "total" => $total,
    "heading" => get_string("teachers", "local_teacher"),
    "headingprefix" => get_string("directoryheadingprefix", "local_teacher"),
    "headingaccent" => get_string("directoryheadingaccent", "local_teacher"),
    "intro" => get_string("directoryintro", "local_teacher"),
    "filterstitle" => get_string("filterstitle", "local_teacher"),
    "categorylabel" => get_string("filtercategory", "local_teacher"),
    "categoryoptions" => $categoryoptions,
    "sortlabel" => get_string("sortby", "local_teacher"),
    "sortoptions" => [
        ["id" => "az", "name" => get_string("sortaz", "local_teacher"), "selected" => $sort === "az"],
        ["id" => "za", "name" => get_string("sortza", "local_teacher"), "selected" => $sort === "za"],
    ],
    "searchlabel" => get_string("searchteachers", "local_teacher"),
    "search" => $search,
    "searchaction" => (new moodle_url("/local/teacher/index.php"))->out(false),
    "searchbutton" => get_string("applyfilters", "local_teacher"),
    "countlabel" => get_string("teachercount", "local_teacher", $total),
    "teachers" => $cards,
    "hasteachers" => !empty($cards),
    "emptylabel" => get_string("noteachers", "local_teacher"),
    "paginghtml" => $OUTPUT->render($pagingbar),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_teacher/directory", $data);
echo $OUTPUT->footer();
