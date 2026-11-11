# Teachers directory (local_teacher)

Native teacher directory for Moodle 4.5 LTS and Moodle 5.x. Teachers are discovered dynamically from **direct course-level role assignments**. No scheduled synchronization or duplicated profile records are needed.

## Installation

Extract this package into `<moodle>/local/teacher/` (the directory must be named `teacher`), then open Site administration > Notifications to install it. Alternatively, upload the ZIP using Site administration > Plugins > Install plugins.

Visit `/local/teacher/index.php` for the directory and `/local/teacher/view.php?id=USERID` for an individual profile. A navigation link is registered with the standard Moodle local-plugin navigation callback.

## Administration

Site administration > Plugins > Local plugins > Teachers contains these options:

- Enable/disable directory.
- Multiple course role IDs, with **ID 3 selected initially** (the numeric role ID can differ across Moodle installations).
- Allow guests to view the public teacher directory (disabled by default).
- Enable/disable native private-message contact form (enabled by default).

The adjacent **Excluded teachers** admin page lets site administrators search for eligible teachers and hide or restore them individually. Exclusions take effect immediately, including direct profile URL access.

## Privacy and performance

Only active, confirmed, non-deleted, non-suspended users with selected roles assigned **directly to visible courses** appear. Role assignments inherited from system or category contexts are not treated as course assignments. Hidden courses do not appear, and teachers who teach *only* hidden courses are omitted. Directory and profile pages are access-controlled; guest access is optional.

The profile displays only Moodle display name, Moodle user picture, the existing **`user.description`** text and publicly visible course titles. No direct email, phone number or custom profile fields are exposed. The description is formatted and cleaned using Moodle's `format_text()`.

Contact uses Moodle's private messaging with recipient permission and block checks. Guest users cannot send messages. A 30-second session throttle, CSRF protection and a 2,000-character message limit provide extra basic abuse protection; Moodle's own messaging policies remain authoritative. Contact messages are stored by Moodle core, not this plugin.

Teacher discovery is paginated and does not retrieve every user/course at once. Course data is batched for each page (no per-teacher query). The only plugin table stores explicit exclusions and is covered by the Moodle Privacy API.

## Quality checks

Run the canonical validator in check-only mode when a complete checkout is available:

`python3 /path/to/moodle-plugin-validate/.chatgpt/run.py --check /path/to/local/teacher`

The validation rules are defined by https://github.com/EduardoKrausME/moodle-plugin-validate/blob/master/.chatgpt/INSTRUCTIONS.md.