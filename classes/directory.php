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
 * Queries and configuration for discoverable teachers.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

use core_user\fields;

/**
 * Query teachers by their direct course-level role assignments.
 */
final class directory {
    /**
     * Check whether the directory is enabled.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        $setting = get_config("local_teacher", "enabled");
        return $setting === false || (bool) $setting;
    }

    /**
     * Check guest access configuration.
     *
     * @return bool
     */
    public static function allows_guests(): bool {
        return (bool) get_config("local_teacher", "allowguest");
    }

    /**
     * Check contact form configuration.
     *
     * @return bool
     */
    public static function allows_contact(): bool {
        $setting = get_config("local_teacher", "allowcontact");
        return $setting === false || (bool) $setting;
    }

    /**
     * Get selected role IDs; role 3 is the first-install default.
     *
     * @return int[]
     */
    public static function role_ids(): array {
        $raw = get_config("local_teacher", "roleids");
        if ($raw === false) {
            return [3];
        }
        $ids = array_map("intval", explode(",", (string) $raw));
        return array_values(array_unique(array_filter($ids, static function(int $id): bool {
            return $id > 0;
        })));
    }

    /**
     * Build the common SQL conditions and bound parameters.
     *
     * @param string $search Optional name search.
     * @param bool $excluded Whether excluded teachers should be included.
     * @return array{0:string,1:array}
     */
    private static function conditions(string $search = "", bool $excluded = false): array {
        global $DB;

        $roleids = self::role_ids();
        if (!$roleids) {
            return ["1 = 0", []];
        }
        [$insql, $params] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, "teacherrole");
        $condition = "ra.roleid $insql AND u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
                      AND c.visible = 1";
        if (!$excluded) {
            $condition .= " AND NOT EXISTS (
                SELECT 1 FROM {local_teacher_excluded} exclusions WHERE exclusions.userid = u.id
            )";
        }
        if ($search !== "") {
            $like = "%" . $DB->sql_like_escape($search) . "%";
            $condition .= " AND (" . $DB->sql_like("u.firstname", ":firstname", false) .
                " OR " . $DB->sql_like("u.lastname", ":lastname", false) .
                " OR " . $DB->sql_like($DB->sql_concat("u.firstname", "' '", "u.lastname"), ":fullname", false) . ")";
            $params["firstname"] = $like;
            $params["lastname"] = $like;
            $params["fullname"] = $like;
        }
        return [$condition, $params];
    }

    /**
     * Count directory members.
     *
     * @param string $search Search terms.
     * @return int
     */
    public static function count(string $search = ""): int {
        global $DB;
        [$conditions, $params] = self::conditions($search);
        $sql = "SELECT COUNT(DISTINCT u.id)
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
                  JOIN {course} c ON c.id = ctx.instanceid
                 WHERE $conditions";
        $params["level"] = CONTEXT_COURSE;
        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Get a page of directory members.
     *
     * @param string $search Search terms.
     * @param int $page Zero-based page index.
     * @param int $pagesize Maximum members per page.
     * @return array
     */
    public static function get_teachers(string $search = "", int $page = 0, int $pagesize = 18): array {
        global $DB;
        [$conditions, $params] = self::conditions($search);
        $names = fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $sql = "SELECT DISTINCT u.id, u.picture, u.imagealt, $names
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
                  JOIN {course} c ON c.id = ctx.instanceid
                 WHERE $conditions
              ORDER BY u.lastname, u.firstname, u.id";
        $params["level"] = CONTEXT_COURSE;
        return $DB->get_records_sql($sql, $params, max(0, $page) * $pagesize, $pagesize);
    }

    /**
     * Get one visible directory member with their full profile description.
     *
     * @param int $userid Moodle user ID.
     * @return \stdClass|null
     */
    public static function get_teacher(int $userid): ?\stdClass {
        global $DB;
        [$conditions, $params] = self::conditions();
        $params["userid"] = $userid;
        $params["level"] = CONTEXT_COURSE;
        $sql = "SELECT u.* FROM {user} u
                 WHERE u.id = :userid AND EXISTS (
                    SELECT 1 FROM {role_assignments} ra
                      JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
                      JOIN {course} c ON c.id = ctx.instanceid
                     WHERE ra.userid = u.id AND $conditions
                 )";
        $record = $DB->get_record_sql($sql, $params, IGNORE_MISSING);
        return $record ?: null;
    }

    /**
     * Load visible courses for multiple teachers in one query.
     *
     * @param int[] $userids Teacher user IDs.
     * @param bool $withdetails Include the summary when displaying an individual profile.
     * @return array Courses indexed by teacher ID.
     */
    public static function courses_for(array $userids, bool $withdetails = false): array {
        global $DB;
        $result = [];
        $roleids = self::role_ids();
        if (!$userids || !$roleids) {
            return $result;
        }
        [$userinsql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, "teacheruser");
        [$roleinsql, $roleparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, "teacherrole");
        $params = array_merge($userparams, $roleparams, ["level" => CONTEXT_COURSE]);
        $detailfields = $withdetails ? ", c.summary, c.summaryformat" : "";
        $sql = "SELECT DISTINCT ra.userid AS teacherid, c.id AS courseid, c.fullname $detailfields
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
                  JOIN {course} c ON c.id = ctx.instanceid
                 WHERE ra.userid $userinsql AND ra.roleid $roleinsql AND c.visible = 1
              ORDER BY c.fullname ASC, c.id ASC";
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $record) {
            $result[$record->teacherid][] = $record;
        }
        $records->close();
        return $result;
    }

    /**
     * Get excluded users for administration.
     *
     * @return array
     */
    public static function excluded(): array {
        global $DB;
        $names = fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $sql = "SELECT u.id, $names, e.timecreated
                  FROM {local_teacher_excluded} e
                  JOIN {user} u ON u.id = e.userid
              ORDER BY u.lastname, u.firstname, u.id";
        return $DB->get_records_sql($sql);
    }

    /**
     * Exclude a teacher from all public directory endpoints.
     *
     * @param int $userid Teacher user ID.
     */
    public static function exclude(int $userid): void {
        global $DB;
        if (!self::get_teacher($userid)) {
            throw new \moodle_exception("teachernotfound", "local_teacher");
        }
        $DB->insert_record("local_teacher_excluded", (object) [
            "userid" => $userid,
            "timecreated" => time(),
        ]);
    }

    /**
     * Restore teacher visibility in the directory.
     *
     * @param int $userid Teacher user ID.
     */
    public static function include(int $userid): void {
        global $DB;
        $DB->delete_records("local_teacher_excluded", ["userid" => $userid]);
    }
}
