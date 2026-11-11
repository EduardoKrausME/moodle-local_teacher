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
 * Administrator configuration for the teacher directory.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage("local_teacher", get_string("pluginname", "local_teacher"));
    $ADMIN->add("localplugins", $settings);

    $settings->add(new admin_setting_configcheckbox(
        "local_teacher/enabled",
        get_string("enabled", "local_teacher"),
        get_string("enabled_desc", "local_teacher"),
        1
    ));

    $roles = [];
    $systemcontext = context_system::instance();
    foreach ($DB->get_records("role", [], "sortorder ASC", "id, name, shortname") as $role) {
        $roles[$role->id] = role_get_name($role, $systemcontext, ROLENAME_BOTH);
    }
    $settings->add(new admin_setting_configmultiselect(
        "local_teacher/roleids",
        get_string("roles", "local_teacher"),
        get_string("roles_desc", "local_teacher"),
        [3],
        $roles
    ));

    $settings->add(new admin_setting_configcheckbox(
        "local_teacher/allowguest",
        get_string("allowguest", "local_teacher"),
        get_string("allowguest_desc", "local_teacher"),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        "local_teacher/allowcontact",
        get_string("allowcontact", "local_teacher"),
        get_string("allowcontact_desc", "local_teacher"),
        1
    ));

    $ADMIN->add("localplugins", new admin_externalpage(
        "local_teacher_exclusions",
        get_string("exclusions", "local_teacher"),
        new moodle_url("/local/teacher/exclusions.php"),
        "moodle/site:config"
    ));
}
