# BIT-InternshipHub overhaul notes

## Changes in this first pass

- Reworked the public home page into a responsive, editorial-style landing page with clear paths for students and employers.
- Added a small shared stylesheet so public pages with existing Bootstrap/MDB markup have consistent typography, colors, spacing, and form/card treatment.
- Hardened PHP sessions with HttpOnly, SameSite=Lax cookies, Secure cookies on HTTPS, and session ID rotation at login.
- Set the MySQL connection to `utf8mb4`, stopped returning raw database errors to visitors, and moved the login timestamp update to a prepared statement.
- Added reusable CSRF token helpers for the form handlers as they are restored.
- Added a permission-guarded superadmin overview with basic platform metrics. It follows the current `users.permissions` model and does not create a second identity system.
- Added a CLI-only demo account seeder for student, employer, and superadmin roles. It never resets passwords on existing accounts and is not reachable over HTTP.
- Database credentials can now be supplied through `INTERNSHIP_DB_USER` and `INTERNSHIP_DB_PASS`.

## Important findings

The repository contains many zero-byte files, not just missing styles. The employer portal, admin portal, student account management pages, and several shared includes are empty, so those workflows still need implementation. `internship.sql` now defines the full platform schema, and `scripts/setup_internship_schema.php` applies it and safely upgrades the existing local database without dropping rows. The existing database has six applications whose internship IDs do not exist; the installer preserves those records and therefore skips only the application-to-internship foreign key until those orphan records are reviewed. All other relationships are installed when their existing data supports them. Run the setup script before the demo-account seeder.

`BASEURL` previously pointed to `/internship/` although the workspace/app directory is `internship_mgt`; update it to match the actual deployed URL if your WAMP virtual path differs.

## Recommended next build steps

1. Review and resolve the six legacy applications that refer to missing internship records, then rerun the schema setup so the application-to-internship foreign key can be enabled.
2. Build login, registration, and logout flows around prepared statements, server-side role checks, password policy, rate limits, CSRF validation, and audit events.
3. Implement employer listing management and student applications with ownership checks, pagination, and safe resume upload handling.
4. Add superadmin user moderation, listing review, and audit log only after the data model and operator provisioning process are agreed.
5. Add production deployment controls: HTTPS, least-privilege database user, backups, error logging/monitoring, and a supported PHP/MySQL version.

The profile, token, notification, status-history, and audit tables provide storage; their corresponding user-facing workflows and write paths still need implementation. The CSRF helpers are infrastructure for the form handlers; each mutating form still needs to include `csrf_field()` and reject POST requests where `csrf_is_valid()` is false. No full application test run was performed.

## External opportunity search

The internships page now includes a same-origin JSON search endpoint at `api/opportunities.php`. It uses Jooble server-side, so its API key is never sent to the browser. Search defaults to Ghana and also offers Africa and International scopes. External listings are labeled and link to the original posting; they are not stored as BIT listings and cannot be applied to through the local application workflow. Responses are cached in `external_opportunity_search_cache` for two hours, requests are limited to five pages of 12 items, and cache misses are limited to ten per client per hour. A WebSocket is unnecessary for user-triggered search; ordinary HTTP requests are simpler for this feature.

To enable live results, request a Jooble API key for the intended market and set `JOOBLE_API_KEY` in the Apache/PHP environment. Do not put the key in JavaScript or commit it. Then run `php scripts/setup_internship_schema.php` to create the cache table. Until the key is configured, local internship listings continue to work and the external section explains that live search is disconnected. Jooble documents a 500-request lifetime limit for its free API plan, so the cache is enabled from the start; confirm current market access, limits, and display terms with Jooble before launch.

## Saved and recently viewed internships

Student accounts can save up to twenty published local internships from the opportunity detail page and remove them from the saved list on `myaccount.php`. The save action is POST-only, CSRF-protected, role-checked, and scoped to the current active student. Guests keep up to ten recently viewed listing IDs in their PHP session; signed-in students keep up to twenty in `recently_viewed_internships`. These tables are included in `internship.sql`; run the schema setup script after pulling this change. External Jooble listings are not saved because applications/bookmarks belong on their original provider site.

## Secure messaging

See [SECURE_MESSAGING_ARCHITECTURE.md](SECURE_MESSAGING_ARCHITECTURE.md) for the self-hosted Matrix end-to-end messaging design. Server endpoints now verify Matrix account ownership and ensure linked rooms use Matrix encryption and have only the student/employer attached to that application. The schema stores account/room references only. Message bodies and cryptographic keys remain on clients/Matrix. The project still needs a permanent homeserver domain, TLS endpoint, JavaScript SDK build, Matrix account enrollment, and key recovery/verification UI before users can message. Do not treat ordinary PHP/MySQL storage encryption or HTTPS alone as end-to-end encryption.
