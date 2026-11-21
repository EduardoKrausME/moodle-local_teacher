# Teachers for Moodle

**Teachers** (`local_teacher`) adds a searchable directory of teaching staff to Moodle. It uses existing Moodle accounts, course role assignments and profile information, so there is no separate teacher database to maintain and no scheduled synchronisation to configure.

Visitors can browse teacher cards, open detailed profiles and discover the courses each teacher delivers. Site administrators choose which roles count as teachers, control access to the directory and can hide individual teachers without affecting their courses or enrolments.

## Features

- **Automatic teacher discovery.** Teachers are identified through selected roles assigned directly at course level.
- **Searchable directory.** Search by first name, last name or full name, with paginated results and teacher profile pictures.
- **Individual teacher profiles.** Display a biography, optional curriculum details and the courses associated with each teacher.
- **Course showcase.** Show course overview images, titles and short descriptions, linking directly to the corresponding Moodle courses.
- **Native profile fields.** Store graduation date and LinkedIn URL using Moodle's custom user profile fields.
- **Private messaging.** Allow signed-in users to contact teachers using Moodle's own messaging system, subject to its permissions and blocking rules.
- **Administrative exclusions.** Hide or restore a teacher in the directory without changing the user's roles, enrolments or account.
- **Integrated navigation.** Add a Teachers link to Moodle's primary navigation when the directory is enabled and accessible.
- **Responsive interface.** Mustache templates and responsive styles, with English and Brazilian Portuguese language strings.

## How teacher discovery works

The directory is generated from Moodle's existing data. A user appears when all of the following conditions are met:

1. The user has one of the **configured teacher roles assigned directly in a course context**.
2. At least one course with that role assignment is visible.
3. The user account is confirmed, active, and neither suspended nor deleted.
4. The user has not been excluded from the directory by a site administrator.

A role assigned only at system or course-category level does **not** qualify a user for the directory. Users meeting the criteria appear once, even when they teach multiple courses.

For example, a teacher assigned to three visible courses appears as one person in the directory, with links to those courses on their profile. If their qualifying courses become hidden, they are no longer listed.

No background synchronisation is required: the directory reads role assignments and course visibility from Moodle when the pages are requested.

## Installation

Install the plugin in your Moodle site's `local/teacher` directory.

From the Moodle installation root:

```bash
git clone https://github.com/EduardoKrausME/moodle-local_teacher.git local/teacher
php admin/cli/upgrade.php --non-interactive
```

Alternatively, upload the plugin ZIP through Moodle's plugin installer and complete the installation from Site administration.

The plugin's component name is `local_teacher`. On first installation, it creates a **Curriculum** category containing two optional native user profile fields:

- **Graduation:** a date used to calculate the number of complete years since graduation.
- **LinkedIn:** an optional link to a teacher's LinkedIn profile.

These fields are stored in Moodle's standard custom profile field tables, not in a separate plugin-managed curriculum database.

## Configuration

Go to **Site administration > Plugins > Local plugins > Teachers**.

| Setting | Description | Default |
| --- | --- | --- |
| Enable teacher directory | Enables or disables the directory and teacher profile pages. | Enabled |
| Teacher roles | Selects which course-level role assignments identify teachers. | Role ID `3` |
| Allow visitors without an account | Makes the directory and its teacher profiles accessible without signing in. | Disabled |
| Enable contact form | Allows eligible signed-in users to send private messages to teachers. | Enabled |

The default role selection uses ID `3`. Check the roles on your own site rather than assuming that a particular role always has the same ID.

### Hide individual teachers

Open **Site administration > Plugins > Local plugins > Excluded teachers** (the exact menu labels depend on the active language pack).

Search for a teacher and select **Exclude** to remove them from the directory immediately. Their Moodle profile, courses, role assignments and enrolments remain unchanged. Select **Restore** to include them again.

An excluded teacher cannot be opened through the plugin's individual profile page, even when somebody knows the URL.

## Using the directory

The directory is available at:

```text
https://your-moodle.example/local/teacher/
```

The page displays teacher cards with photographs, names and up to three course names. Search results are paginated at 18 teachers per page.

Selecting a card opens a teacher profile, with a readable URL combining the Moodle user ID and a name-based slug:

```text
https://your-moodle.example/local/teacher/view.php/42-jane-smith
```

The ID keeps URLs unambiguous when two teachers share a name. The plugin also recognises supported older link formats and redirects to the canonical profile URL where appropriate.

### Completing a teacher profile

Teachers maintain their information through their normal Moodle user profile, subject to existing profile-editing permissions:

- **Profile picture:** displayed in the directory and on the teacher's page.
- **Description:** used as the teacher biography.
- **Graduation:** used to display elapsed years since graduation, *not* verified years of teaching experience.
- **LinkedIn:** shown as an external link when the custom field is public and contains a valid HTTPS URL on `linkedin.com`.

The profile also shows the teacher's visible courses, including overview images and descriptions from those courses' settings. Course links do not bypass Moodle's normal authentication, enrolment or access restrictions.

## Access, messaging and privacy

### Directory access

Authenticated users must have the system-level capability `local/teacher:view` to view the directory. Anonymous visitors can access it only when **Allow visitors without an account** is enabled.

**Public access exposes teacher information.** When anonymous access is enabled, visitors may see teacher names, profile pictures, biographies, curriculum information marked public, and details of visible courses. Review your organisation's privacy requirements before enabling it.

### Contacting a teacher

The contact action is available only when the contact setting is enabled, the visitor is signed in, the recipient is another user, and Moodle's messaging API permits the message.

Messages are sent through Moodle's native private messaging functionality. The form:

- Uses Moodle's session-key verification.
- Sends plain-text messages with a maximum of 2,000 characters.
- Enforces a minimum 30-second interval between submissions in the same user session.
- Respects Moodle's messaging permissions and user blocking rules.

The plugin does not implement a separate inbox or message delivery system.

### Stored data

Teacher biographies, photographs, curriculum fields and course information remain in Moodle's native tables. The plugin maintains one dedicated table, `local_teacher_excluded`, which records the user IDs and timestamps of manual directory exclusions.

The plugin includes a Moodle Privacy API provider for exporting and deleting those exclusion records in response to privacy requests.

## Troubleshooting

**A teacher is missing from the directory.** Check that the selected teacher role is assigned directly in at least one visible course, the account is confirmed and not suspended, and the teacher is not on the exclusion list. A role assigned only at site or category level is not sufficient.

**The directory is not accessible.** Confirm that the directory is enabled, the visitor access setting is appropriate, and the user's role has the `local/teacher:view` capability where required.

**The contact button is not shown.** Confirm that contact is enabled and the visitor is signed in, then check Moodle's messaging permissions, privacy settings and user blocking restrictions.

**Curriculum information does not appear.** Make sure the optional profile fields have values and their visibility is set to public. Only valid LinkedIn URLs are displayed.

## Development

The plugin uses Moodle's role assignments, user profiles, course metadata, navigation hooks, messaging API and privacy API. The directory is rendered with Mustache templates and uses a small AMD module for the contact dialog.

Automated PHPUnit coverage is included for teacher discovery, exclusions, profile URLs and curriculum fields. The repository also provides a GitHub Actions workflow for plugin validation and testing.

Issues and contributions: [GitHub repository](https://github.com/EduardoKrausME/moodle-local_teacher).

## License

Copyright © 2026 [Eduardo Kraus](https://eduardokraus.com).

Licensed under the [GNU General Public License, version 3 or later](LICENSE).
