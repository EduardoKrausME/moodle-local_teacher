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
 * Native Moodle curriculum profile fields.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

defined("MOODLE_INTERNAL") || die();

/**
 * Create and read public curriculum data using Moodle's profile fields.
 */
final class profile_fields {
    /** @var string Shortname of the graduation date field. */
    public const GRADUATION = "local_teacher_graduation";

    /** @var string Shortname of the LinkedIn field. */
    public const LINKEDIN = "local_teacher_linkedin";

    /**
     * Create the category and two optional native fields during initial install.
     *
     * Existing categories and field definitions are preserved.
     */
    public static function install(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/user/profile/lib.php");
        $name = get_string("curriculumcategory", "local_teacher");
        $category = $DB->get_record("user_info_category", ["name" => $name], "id", IGNORE_MULTIPLE);
        if ($category) {
            $categoryid = (int) $category->id;
        } else {
            $sortorder = (int) $DB->get_field_sql("SELECT COALESCE(MAX(sortorder), 0) FROM {user_info_category}");
            $categoryid = (int) $DB->insert_record("user_info_category", (object) [
                "name" => $name,
                "sortorder" => $sortorder + 1,
            ]);
        }

        $fields = [
            [
                "shortname" => self::GRADUATION,
                "name" => get_string("graduationfield", "local_teacher"),
                "datatype" => "datetime",
                "param1" => "1940",
                "param2" => "2100",
                "param3" => "0",
                "defaultdata" => "0",
                "sortorder" => 1,
            ],
            [
                "shortname" => self::LINKEDIN,
                "name" => get_string("linkedinfield", "local_teacher"),
                "datatype" => "text",
                "param1" => "60",
                "param2" => "255",
                "param3" => "0",
                "defaultdata" => "",
                "sortorder" => 2,
            ],
        ];

        foreach ($fields as $field) {
            if ($DB->record_exists("user_info_field", ["shortname" => $field["shortname"]])) {
                continue;
            }
            $record = (object) array_merge($field, [
                "categoryid" => $categoryid,
                "description" => "",
                "descriptionformat" => FORMAT_HTML,
                "required" => 0,
                "locked" => 0,
                "visible" => PROFILE_VISIBLE_ALL,
                "forceunique" => 0,
                "signup" => 0,
                "defaultdataformat" => FORMAT_PLAIN,
                "param4" => "",
                "param5" => "",
            ]);
            $DB->insert_record("user_info_field", $record);
        }
    }

    /**
     * Return only profile fields whose visibility is public.
     *
     * @param int $userid Moodle user ID.
     * @return array{graduation:int,linkedin:string}
     */
    public static function get_public_data(int $userid): array {
        global $DB;

        $data = ["graduation" => 0, "linkedin" => ""];
        $sql = "SELECT f.shortname, d.data
                  FROM {user_info_field} f
                  JOIN {user_info_data} d ON d.fieldid = f.id
                 WHERE d.userid = :userid AND f.visible = :visible
                       AND f.shortname IN (:graduationfield, :linkedinfield)";
        $records = $DB->get_records_sql($sql, [
            "userid" => $userid,
            "visible" => 2,
            "graduationfield" => self::GRADUATION,
            "linkedinfield" => self::LINKEDIN,
        ]);
        if (isset($records[self::GRADUATION])) {
            $value = trim((string) $records[self::GRADUATION]->data);
            if (preg_match("/^-?\d+$/D", $value)) {
                $data["graduation"] = (int) $value;
            }
        }
        if (isset($records[self::LINKEDIN])) {
            $data["linkedin"] = trim((string) $records[self::LINKEDIN]->data);
        }
        return $data;
    }

    /**
     * Calculate elapsed complete calendar years since graduation.
     *
     * @param int $timestamp Graduation date as timestamp.
     * @param int|null $now Current time override for tests.
     * @return int|null Null if unknown or in the future.
     */
    public static function years_since_graduation(int $timestamp, ?int $now = null): ?int {
        $now = $now ?? time();
        if ($timestamp === 0 || $timestamp > $now) {
            return null;
        }
        $timezone = new \DateTimeZone("UTC");
        $graduation = (new \DateTimeImmutable("@" . $timestamp))->setTimezone($timezone);
        $today = (new \DateTimeImmutable("@" . $now))->setTimezone($timezone);
        return $graduation->diff($today)->y;
    }

    /**
     * Accept only HTTPS URLs on linkedin.com and its subdomains.
     *
     * @param string $url Entered URL.
     * @return string|null Safe URL or null.
     */
    public static function linkedin_url(string $url): ?string {
        $url = trim($url);
        if ($url === "" || preg_match("/\s/u", $url)) {
            return null;
        }
        if (strpos($url, "://") === false) {
            $url = "https://" . $url;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts["scheme"], $parts["host"])) {
            return null;
        }
        $host = strtolower(rtrim($parts["host"], "."));
        if (strtolower($parts["scheme"]) !== "https"
                || !preg_match("/(?:^|\.)linkedin\.com$/D", $host)
                || isset($parts["user"]) || isset($parts["pass"])
                || (isset($parts["port"]) && $parts["port"] !== 443)) {
            return null;
        }
        return $url;
    }
}
