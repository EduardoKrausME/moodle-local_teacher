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
 * Curriculum profile fields regression tests.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

defined("MOODLE_INTERNAL") || die();

/**
 * @covers \local_teacher\profile_fields
 */
final class profile_fields_test extends \advanced_testcase {
    /**
     * Initial install is idempotent and uses native profile field datatypes.
     */
    public function test_install_does_not_duplicate_fields(): void {
        global $DB;
        $this->resetAfterTest();
        profile_fields::install();
        profile_fields::install();
        $graduation = $DB->get_record("user_info_field", ["shortname" => profile_fields::GRADUATION], "*", MUST_EXIST);
        $linkedin = $DB->get_record("user_info_field", ["shortname" => profile_fields::LINKEDIN], "*", MUST_EXIST);
        $this->assertSame(1, $DB->count_records("user_info_field", ["shortname" => profile_fields::GRADUATION]));
        $this->assertSame(1, $DB->count_records("user_info_field", ["shortname" => profile_fields::LINKEDIN]));
        $this->assertSame("datetime", $graduation->datatype);
        $this->assertSame("text", $linkedin->datatype);
        $this->assertEquals($graduation->categoryid, $linkedin->categoryid);
    }

    /**
     * Only globally visible profile fields may be used in public directory.
     */
    public function test_profile_field_privacy(): void {
        global $DB;
        $this->resetAfterTest();
        profile_fields::install();
        $teacher = $this->getDataGenerator()->create_user();
        $linkedin = $DB->get_record("user_info_field", ["shortname" => profile_fields::LINKEDIN], "*", MUST_EXIST);
        $DB->insert_record("user_info_data", (object) [
            "userid" => $teacher->id, "fieldid" => $linkedin->id,
            "data" => "https://www.linkedin.com/in/example", "dataformat" => 0,
        ]);
        $this->assertSame("https://www.linkedin.com/in/example", profile_fields::get_public_data($teacher->id)["linkedin"]);
        $DB->set_field("user_info_field", "visible", 1, ["id" => $linkedin->id]);
        $this->assertSame("", profile_fields::get_public_data($teacher->id)["linkedin"]);
    }

    /**
     * Years are counted precisely, and unsafe LinkedIn links are rejected.
     */
    public function test_calculation_and_url_safety(): void {
        $graduation = gmmktime(0, 0, 0, 11, 15, 2010);
        $now = gmmktime(0, 0, 0, 11, 14, 2026);
        $this->assertSame(15, profile_fields::years_since_graduation($graduation, $now));
        $this->assertSame(16, profile_fields::years_since_graduation($graduation, $now + 86400));
        $this->assertNull(profile_fields::years_since_graduation(0, $now));
        $this->assertNull(profile_fields::linkedin_url("https://linkedin.com.evil.example/in/foo"));
        $this->assertNull(profile_fields::linkedin_url("http://linkedin.com/in/foo"));
        $this->assertSame("https://www.linkedin.com/in/foo", profile_fields::linkedin_url("www.linkedin.com/in/foo"));
    }
}
