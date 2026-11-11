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
require_once($CFG->libdir . "/filelib.php");
require_once($CFG->dirroot . "/message/lib.php");

use core\output\notification;
use core_message\api;
use local_teacher\directory;
use local_teacher\presentation;
use local_teacher\profile_fields;
use local_teacher\profile_url;

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

$identifier = required_param("id", PARAM_RAW_TRIMMED);
$teacher = profile_url::resolve($identifier);
if (!$teacher) {
    throw new moodle_exception("teachernotfound", "local_teacher");
}
$id = (int) $teacher->id;

$url = profile_url::for_teacher($teacher);
if (ctype_digit($identifier) && !data_submitted()) {
    redirect($url);
}
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$PAGE->set_title(fullname($teacher));


$isownprofile = isloggedin() && !isguestuser() && (int) $USER->id === $id;
$isadministrator = is_siteadmin();
$caneditown = $isownprofile
    && has_capability("moodle/user:editownprofile", $context)
    && get_auth_plugin($teacher->auth)->can_edit_profile();
$canedit = $isadministrator || $caneditown;
$editurl = "";
if ($canedit) {
    $editpath = $isadministrator ? "/user/editadvanced.php" : "/user/edit.php";
    $editurl = (new moodle_url($editpath, ["id" => $id, "returnto" => "profile"]))->out(false);
}

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
$courses = directory::courses_for([$id], true);
$courseitems = presentation::profile_courses($courses[$id] ?? []);
$curriculum = profile_fields::get_public_data($id);
$years = profile_fields::years_since_graduation($curriculum["graduation"]);
$linkedinurl = profile_fields::linkedin_url($curriculum["linkedin"]);

$data = [
    "fullname" => fullname($teacher),
    "pictureurl" => presentation::picture_url($teacher, 512),
    "backurl" => (new moodle_url("/local/teacher/index.php"))->out(false),
    "years" => $years ?? 0,
    "hasexperience" => $years !== null,
    "isoneyear" => $years === 1,
    "coursecount" => count($courseitems),
    "haslinkedin" => $linkedinurl !== null,
    "linkedinurl" => $linkedinurl ?? "",
    "biohtml" => $bio,
    "hasbio" => !empty($bio),
    "courses" => $courseitems,
    "hascourses" => !empty($courseitems),
    "coursescards" => $courseitems,
    "cancontact" => $cancontact,
    "canedit" => $canedit,
    "editownprofile" => $isownprofile,
    "editurl" => $editurl,
    "contactaction" => $url->out(false),
    "sesskey" => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("local_teacher/profile", $data);
echo $OUTPUT->footer();
