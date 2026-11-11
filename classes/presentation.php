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
 * Prepare Mustache template data for teacher cards and profiles.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

use moodle_url;
use stdClass;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Teacher view models.
 */
final class presentation {
    /**
     * Construct a public picture URL using the Moodle user picture API.
     *
     * @param stdClass $user Moodle user object.
     * @param int $size Requested picture size.
     * @return string
     */
    public static function picture_url(stdClass $user, int $size = 100): string {
        global $PAGE;
        $picture = new user_picture($user);
        $picture->size = $size;
        return $picture->get_url($PAGE)->out(false);
    }

    /**
     * Prepare the compact directory card.
     *
     * @param stdClass $teacher Moodle user.
     * @param array $courses Course records.
     * @return array
     */
    public static function card(stdClass $teacher, array $courses): array {
        $name = fullname($teacher);
        $listedcourses = [];
        foreach (array_slice($courses, 0, 3) as $course) {
            $listedcourses[] = ["name" => format_string($course->fullname)];
        }
        return [
            "fullname" => $name,
            "pictureurl" => self::picture_url($teacher, 512),
            "profileurl" => (new moodle_url("/local/teacher/view.php", ["id" => $teacher->id]))->out(false),
            "courses" => $listedcourses,
            "hascourses" => !empty($listedcourses),
        ];
    }

    /**
     * Prepare public course links for a teacher profile.
     *
     * @param array $courses Course records.
     * @return array
     */
    public static function courses(array $courses): array {
        $items = [];
        foreach ($courses as $course) {
            $items[] = [
                "name" => format_string($course->fullname),
                "url" => (new moodle_url("/course/view.php", ["id" => $course->courseid]))->out(false),
            ];
        }
        return $items;
    }


    /**
     * Prepare course cards with native overview images and a short summary.
     *
     * @param array $courses Public course records with summaries.
     * @return array
     */
    public static function profile_courses(array $courses): array {
        global $OUTPUT;

        $items = [];
        $storage = get_file_storage();
        foreach ($courses as $course) {
            $context = \context_course::instance((int) $course->courseid);
            $image = "";
            $files = $storage->get_area_files($context->id, "course", "overviewfiles", 0, "filename", false);
            foreach ($files as $file) {
                if (strpos($file->get_mimetype(), "image/") !== 0) {
                    continue;
                }
                $image = moodle_url::make_pluginfile_url(
                    $context->id, "course", "overviewfiles", null, "/", $file->get_filename(), false
                )->out(false);
                break;
            }
            if ($image === "") {
                $image = $OUTPUT->get_generated_image_for_id((int) $course->courseid);
            }

            $summary = "";
            if (!empty($course->summary)) {
                $formatted = format_text($course->summary, (int) $course->summaryformat, [
                    "context" => $context,
                    "noclean" => false,
                    "para" => false,
                ]);
                $summary = html_entity_decode(strip_tags($formatted), ENT_QUOTES | ENT_HTML5, "UTF-8");
                $summary = trim(preg_replace("/\s+/u", " ", $summary) ?? "");
            }
            if (\core_text::strlen($summary) > 165) {
                $summary = rtrim(\core_text::substr($summary, 0, 162)) . "…";
            }

            $items[] = [
                "name" => format_string($course->fullname, true, ["context" => $context]),
                "summary" => $summary,
                "hassummary" => $summary !== "",
                "imageurl" => $image,
                "url" => (new moodle_url("/course/view.php", ["id" => $course->courseid]))->out(false),
            ];
        }
        return $items;
    }
}
