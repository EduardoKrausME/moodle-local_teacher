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
 * GDPR export and deletion of directory exclusion records.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher\privacy;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy implementation for explicit user exclusions.
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table("local_teacher_excluded", [
            "userid" => "privacy:metadata:userid",
            "timecreated" => "privacy:metadata:timecreated",
        ], "privacy:metadata:excluded");
        return $collection;
    }

    /**
     * Find the system context holding an excluded user's record.
     *
     * @param int $userid User identifier.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contexts = new contextlist();
        if ($DB->record_exists("local_teacher_excluded", ["userid" => $userid])) {
            $contexts->add_system_context();
        }
        return $contexts;
    }

    /**
     * Export a user's exclusion flag.
     *
     * @param approved_contextlist $contextlist Approved user contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        if (!in_array(context_system::instance()->id, $contextlist->get_contextids(), true)) {
            return;
        }
        $record = $DB->get_record("local_teacher_excluded", ["userid" => $contextlist->get_user()->id]);
        if ($record) {
            writer::with_context(context_system::instance())
                ->export_data([get_string("exclusions", "local_teacher")], (object)[
                    "excluded" => true,
                    "timecreated" => $record->timecreated,
                ]);
        }
    }

    /**
     * Delete exclusions when all system-context data expires.
     *
     * @param context $context Expiring context.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records("local_teacher_excluded");
        }
    }

    /**
     * Delete exclusion for an approved user.
     *
     * @param approved_contextlist $contextlist Approved user contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        if (in_array(context_system::instance()->id, $contextlist->get_contextids(), true)) {
            $DB->delete_records("local_teacher_excluded", ["userid" => $contextlist->get_user()->id]);
        }
    }

    /**
     * Add users with exclusion records to the privacy request.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql("userid", "SELECT userid FROM {local_teacher_excluded}", []);
    }

    /**
     * Delete exclusion records for a list of approved users.
     *
     * @param approved_userlist $userlist Approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM || !$userlist->get_userids()) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select("local_teacher_excluded", "userid $insql", $params);
    }
}
