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
 * Primary navigation hook callbacks.
 *
 * @package    local_teacher
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_teacher;

use core\hook\navigation\primary_extend;
use moodle_url;
use navigation_node;

defined("MOODLE_INTERNAL") || die();

/**
 * Callbacks for Moodle navigation hooks.
 */
final class hook_callbacks {
    /**
     * Add the teacher directory to primary navigation when accessible.
     *
     * @param primary_extend $hook The primary navigation hook.
     */
    public static function primary_navigation(primary_extend $hook): void {
        if (!directory::is_enabled()) {
            return;
        }
        if (!directory::allows_guests() && (!isloggedin() || isguestuser())) {
            return;
        }

        $primaryview = $hook->get_primaryview();
        if ($primaryview->find("local_teacher_directory", navigation_node::TYPE_CUSTOM)) {
            return;
        }
        $primaryview->add(
            get_string("teachers", "local_teacher"),
            new moodle_url("/local/teacher/index.php"),
            navigation_node::TYPE_CUSTOM,
            null,
            "local_teacher_directory"
        );
    }
}
