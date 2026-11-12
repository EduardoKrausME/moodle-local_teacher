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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Keep language resolution out of Mustache templates.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

use basic_testcase;

/**
 * Templates should receive fully translated strings from PHP.
 */
final class template_strings_test extends basic_testcase {
    /**
     * Prevent direct string helpers from being reintroduced in templates.
     */
    public function test_templates_do_not_translate_strings(): void {
        $templates = glob(__DIR__ . "/../templates/*.mustache");
        $this->assertNotEmpty($templates);

        foreach ($templates as $template) {
            $content = file_get_contents($template);
            $this->assertNotFalse($content);
            $this->assertStringNotContainsString("{{#str}}", $content, $template);
            $this->assertStringNotContainsString("{{/str}}", $content, $template);
        }
    }
}
