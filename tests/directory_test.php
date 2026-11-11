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
 * Directory discovery and exclusion regression tests.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

defined("MOODLE_INTERNAL") || die();

/**
 * Tests for teacher directory eligibility and exclusions.
 *
 * @covers \local_teacher\directory
 */
final class directory_test extends \advanced_testcase {
    /**
     * Confirm direct role assignments discover active visible teachers.
     */
    public function test_discovers_active_course_teacher(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $roleid = (int) $DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string) $roleid, "local_teacher");
        role_assign($roleid, $teacher->id, \context_course::instance($course->id)->id);
        $this->assertNotNull(directory::get_teacher($teacher->id));
        $this->assertArrayHasKey($teacher->id, directory::get_teachers());
        $this->assertSame(1, directory::count());
    }

    /**
     * Confirm excluded teachers disappear from both endpoints.
     */
    public function test_exclusion_hides_teacher(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $roleid = (int) $DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string) $roleid, "local_teacher");
        role_assign($roleid, $teacher->id, \context_course::instance($course->id)->id);
        directory::exclude($teacher->id);
        $this->assertNull(directory::get_teacher($teacher->id));
        $this->assertSame(0, directory::count());
        $this->assertArrayNotHasKey($teacher->id, directory::get_teachers());
        $this->assertArrayHasKey($teacher->id, directory::excluded());
        directory::include($teacher->id);
        $this->assertNotNull(directory::get_teacher($teacher->id));
        $this->assertArrayHasKey($teacher->id, directory::get_teachers());
        $this->assertArrayNotHasKey($teacher->id, directory::excluded());
    }

    /**
     * Confirm suspended users and hidden courses do not appear.
     */
    public function test_suspended_and_hidden_course_teachers_are_not_listed(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(["visible" => 0]);
        $teacher = $this->getDataGenerator()->create_user();
        $roleid = (int) $DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string) $roleid, "local_teacher");
        role_assign($roleid, $teacher->id, \context_course::instance($course->id)->id);
        $this->assertNull(directory::get_teacher($teacher->id));
        $DB->set_field("course", "visible", 1, ["id" => $course->id]);
        $DB->set_field("user", "suspended", 1, ["id" => $teacher->id]);
        $this->assertNull(directory::get_teacher($teacher->id));
    }
}
