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

use advanced_testcase;
use context_course;

/**
 * Tests for teacher directory eligibility and exclusions.
 *
 * @covers \local_teacher\directory
 */
final class directory_test extends advanced_testcase {
    /**
     * Confirm direct role assignments discover active visible teachers.
     */
    public function test_discovers_active_course_teacher(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $roleid = (int)$DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string)$roleid, "local_teacher");
        role_assign($roleid, $teacher->id, context_course::instance($course->id)->id);
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
        $roleid = (int)$DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string)$roleid, "local_teacher");
        role_assign($roleid, $teacher->id, context_course::instance($course->id)->id);
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
     * Canonical URLs use the teacher ID and a lowercase name slug.
     */
    public function test_teacher_profile_id_and_slug_url(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user([
            "firstname" => "Marcus",
            "lastname" => "Britto",
        ]);
        $roleid = (int) $DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string) $roleid, "local_teacher");
        role_assign($roleid, $teacher->id, context_course::instance($course->id)->id);

        $identifier = $teacher->id . "-marcus-britto";
        $this->assertSame($identifier, profile_url::identifier($teacher));
        $this->assertSame($teacher->id, profile_url::resolve($identifier)->id);
        $this->assertSame($teacher->id, profile_url::resolve((string) $teacher->id)->id);
        $this->assertSame($teacher->id, profile_url::resolve("Marcus%Britto")->id);
        $this->assertStringContainsString(
            "/local/teacher/view.php/" . $identifier,
            profile_url::for_teacher($teacher)->out(false)
        );

        // Old slugs can be redirected safely, because the ID is authoritative.
        $this->assertSame($teacher->id, profile_url::resolve($teacher->id . "-old-name")->id);
        $this->assertNull(profile_url::resolve($teacher->id . "-invalid/segment"));
    }

    /**
     * Duplicate names still produce distinct public URLs.
     */
    public function test_duplicate_profile_names_remain_unique(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $roleid = (int) $DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string) $roleid, "local_teacher");
        $one = $this->getDataGenerator()->create_user(["firstname" => "Marcus", "lastname" => "Britto"]);
        $two = $this->getDataGenerator()->create_user(["firstname" => "Marcus", "lastname" => "Britto"]);
        role_assign($roleid, $one->id, context_course::instance($course->id)->id);
        role_assign($roleid, $two->id, context_course::instance($course->id)->id);

        $this->assertSame($one->id . "-marcus-britto", profile_url::identifier($one));
        $this->assertSame($two->id . "-marcus-britto", profile_url::identifier($two));
        $this->assertNull(profile_url::resolve("Marcus%Britto"));
        $this->assertSame($one->id, profile_url::resolve(profile_url::identifier($one))->id);
        $this->assertSame($two->id, profile_url::resolve(profile_url::identifier($two))->id);

        // Existing duplicate-name links remain readable while both profiles exist.
        $this->assertSame($one->id, profile_url::resolve("Marcus%Britto~" . $one->id)->id);
        directory::exclude($two->id);
        $this->assertNull(profile_url::resolve(profile_url::identifier($two)));
        $this->assertSame($one->id, profile_url::resolve("Marcus%Britto")->id);
    }

    /**
     * The path parser works with PATH_INFO and URL-based fallbacks.
     */
    public function test_teacher_profile_path_parsing(): void {
        $this->assertSame(
            "3-marcus-britto",
            profile_url::path_identifier("/3-marcus-britto", "/local/teacher/view.php/3-wrong")
        );
        $this->assertSame(
            "3-marcus-britto",
            profile_url::path_identifier(null, "/moodle/local/teacher/view.php/3-marcus-britto?from=directory")
        );
        $this->assertNull(profile_url::path_identifier(null, "/moodle/local/teacher/view.php?id=3"));
        $this->assertSame(
            "3-marcus-britto",
            profile_url::path_identifier("/3-marcus-britto", null)
        );
    }

    /**
     * Accented characters and punctuation produce a simple URL slug.
     */
    public function test_teacher_name_slug_normalisation(): void {
        $this->resetAfterTest();
        $teacher = $this->getDataGenerator()->create_user([
            "firstname" => "João Antônio",
            "lastname" => "D'Ávila",
        ]);
        $this->assertSame("joao-antonio-d-avila", profile_url::name_slug($teacher));
    }

    /**
     * Confirm suspended users and hidden courses do not appear.
     */
    public function test_suspended_and_hidden_course_teachers_are_not_listed(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(["visible" => 0]);
        $teacher = $this->getDataGenerator()->create_user();
        $roleid = (int)$DB->get_field("role", "id", ["shortname" => "editingteacher"], MUST_EXIST);
        set_config("roleids", (string)$roleid, "local_teacher");
        role_assign($roleid, $teacher->id, context_course::instance($course->id)->id);
        $this->assertNull(directory::get_teacher($teacher->id));
        $DB->set_field("course", "visible", 1, ["id" => $course->id]);
        $DB->set_field("user", "suspended", 1, ["id" => $teacher->id]);
        $this->assertNull(directory::get_teacher($teacher->id));
    }
}
