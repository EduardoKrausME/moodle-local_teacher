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
 * Teacher biography, course list and optional native message form.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Guest access is optional and guarded by the plugin setting.
require_once(__DIR__ . "/../../config.php");
require_once($CFG->dirroot . "/message/lib.php");

use core\output\notification;
use core_message\api;
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

$id = required_param("id", PARAM_INT);
$teacher = directory::get_teacher($id);
if (!$teacher) {
    throw new moodle_exception("teachernotfound", "local_teacher");
}

$url = new moodle_url("/local/teacher/view.php", ["id" => $id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(fullname($teacher));

$cancontact = directory::allows_contact() && isloggedin() && !isguestuser()
    && (int)$USER->id !== (int)$id
    && api::can_send_message($id, $USER->id);

if (data_submitted() && optional_param("action", "", PARAM_ALPHA) === "send") {
    require_sesskey();
    if (!$cancontact) {
        throw new moodle_exception("contactunavailable", "local_teacher");
    }
    $message = trim(optional_param("message", "", PARAM_RAW_TRIMMED));
    $message = trim(strip_tags($message));
    if ($message === "" || core_text::strlen($message) > 2000) {
        throw new moodle_exception("invalidmessage", "local_teacher");
    }
    if (!empty($SESSION->local_teacher_lastmessage) &&
        $SESSION->local_teacher_lastmessage > time() - 30) {
        throw new moodle_exception("messageslowdown", "local_teacher");
    }
    $result = message_post_message($USER, $teacher, $message, FORMAT_PLAIN);
    if (!$result) {
        throw new moodle_exception("contactunavailable", "local_teacher");
    }
    $SESSION->local_teacher_lastmessage = time();
    redirect($url, get_string("messagesent", "local_teacher"), null, notification::NOTIFY_SUCCESS);
}

$usercontext = context_user::instance($id);
$bio = "";
if (trim((string)$teacher->description) !== "") {
    $description = file_rewrite_pluginfile_urls(
        $teacher->description,
        "pluginfile.php",
        $usercontext->id,
        "user",
        "profile",
        null
    );
    $bio = format_text($description, $teacher->descriptionformat, [
        "context" => $usercontext,
        "noclean" => false,
        "para" => false,
    ]);
}
$courses = directory::courses_for([$id]);
$courseitems = presentation::courses($courses[$id] ?? []);

$data = [
    "fullname" => fullname($teacher),
    "pictureurl" => presentation::picture_url($teacher),
    "backurl" => (new moodle_url("/local/teacher/index.php"))->out(false),
    "backlabel" => get_string("backtodirectory", "local_teacher"),
    "biotitle" => get_string("biography", "local_teacher"),
    "biohtml" => $bio,
    "hasbio" => !empty($bio),
    "nobiolabel" => get_string("nobiography", "local_teacher"),
    "coursestitle" => get_string("teachescourses", "local_teacher"),
    "courses" => $courseitems,
    "hascourses" => !empty($courseitems),
    "nocourses" => get_string("nocourses", "local_teacher"),
    "cancontact" => $cancontact,
    "contacttitle" => get_string("contactteacher", "local_teacher"),
    "contactlabel" => get_string("yourmessage", "local_teacher"),
    "contactbutton" => get_string("sendmessage", "local_teacher"),
    "contactaction" => $url->out(false),
    "sesskey" => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_teacher/profile", $data);
echo $OUTPUT->footer();
