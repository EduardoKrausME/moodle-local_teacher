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
 * Build and resolve public teacher profile URLs.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

use moodle_url;
use stdClass;

/**
 * Encode complete teacher names in links without confusing similar users.
 */
final class profile_url {
    /**
     * Replace spaces with a percent separator, escaping percent signs in names.
     *
     * @param stdClass $teacher Teacher record.
     * @return string
     */
    public static function name_slug(stdClass $teacher): string {
        $name = trim(fullname($teacher));
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        return str_replace(["%", " "], ["%%", "%"], $name);
    }

    /**
     * Retrieve eligible teachers whose display name matches the name slug.
     *
     * @param string $slug Name-based identifier.
     * @return array Teachers indexed by ID.
     */
    private static function matches(string $slug): array {
        global $DB;

        if ($slug === "" || strlen($slug) > 512 || preg_match('/[\x00-\x1f\x7f]/', $slug)) {
            return [];
        }
        $parts = preg_split('/%+/', $slug, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return [];
        }
        $first = reset($parts);
        $last = end($parts);
        $params = [
            "first" => $DB->sql_like_escape($first) . "%",
            "last" => "%" . $DB->sql_like_escape($last),
            "reversefirst" => $DB->sql_like_escape($last) . "%",
            "reverselast" => "%" . $DB->sql_like_escape($first),
        ];

        // Narrow candidates by their first/last name, then respect all of the
        // active-course, role and exclusion checks in directory::get_teacher().
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
                   AND ((" . $DB->sql_like("u.firstname", ":first", false) . "
                     AND " . $DB->sql_like("u.lastname", ":last", false) . ")
                     OR (" . $DB->sql_like("u.lastname", ":reversefirst", false) . "
                     AND " . $DB->sql_like("u.firstname", ":reverselast", false) . "))";

        $matches = [];
        foreach ($DB->get_records_sql($sql, $params) as $candidate) {
            $teacher = directory::get_teacher((int) $candidate->id);
            if ($teacher && self::name_slug($teacher) === $slug) {
                $matches[$teacher->id] = $teacher;
            }
        }
        return $matches;
    }

    /**
     * Use a numeric suffix only if more than one eligible teacher has the same name.
     *
     * @param stdClass $teacher Teacher record.
     * @return string
     */
    public static function identifier(stdClass $teacher): string {
        $slug = self::name_slug($teacher);
        return count(self::matches($slug)) > 1 ? $slug . "~" . $teacher->id : $slug;
    }

    /**
     * Return a Moodle URL that encodes the percent separator as %25.
     *
     * @param stdClass $teacher Teacher record.
     * @return moodle_url
     */
    public static function for_teacher(stdClass $teacher): moodle_url {
        return new moodle_url("/local/teacher/view.php", ["id" => self::identifier($teacher)]);
    }

    /**
     * Resolve new name-based URLs and retain legacy numeric links.
     *
     * @param string $identifier Decoded value of the id query parameter.
     * @return stdClass|null
     */
    public static function resolve(string $identifier): ?stdClass {
        if (ctype_digit($identifier)) {
            return directory::get_teacher((int) $identifier);
        }
        $matches = self::matches($identifier);
        if (count($matches) === 1) {
            return reset($matches);
        }
        if ($matches) {
            return null;
        }
        if (preg_match('/^(.+)~([1-9][0-9]*)$/u', $identifier, $parts)) {
            $matches = self::matches($parts[1]);
            $userid = (int) $parts[2];
            if (count($matches) > 1 && isset($matches[$userid])) {
                return $matches[$userid];
            }
        }
        return null;
    }
}
