# BIT-InternshipHub

**A bridge between what students study and the work they are ready to learn.**

BIT-InternshipHub is an early-stage internship and career-development platform, beginning with Ghana. It is being built to help students and other early-career people discover practical learning opportunities, understand what different careers involve, and make a clearer first move into work. Employers should be able to share meaningful, supervised opportunities and meet emerging talent.

The vision is bigger than a vacancy board: an internship should connect learning with real tasks, guidance, feedback and growing confidence. The platform aims to serve people across study areas—from technology and business to health, agriculture, engineering, education, the creative fields and public service. It does not assume that every person must take a formal internship; projects, research, volunteering, apprenticeships and other supervised experiences can also build career skills.

## What the project is working toward

- **A useful start for students:** browse opportunities, understand role expectations and apply with context.
- **Meaningful placements:** encourage employers to provide real learning goals, supervision and clear conditions.
- **Career discovery across fields:** explain transferable workplace skills and show how different areas of study connect to work.
- **A Ghana-first opportunity experience:** prioritize Ghanaian roles before expanding to Africa and wider markets.
- **Trust and safety:** protect account data, limit access by role and build private, end-to-end encrypted student/employer messaging when the required infrastructure is ready.

## Who it is for

- **Students and early-career people** looking for supervised experience, clearer career direction and opportunities to practise their skills.
- **Employers and organizations** interested in offering structured placements and working with emerging talent.
- **Platform operators** who need a limited administrative view of platform activity.

## What is in the repository today

| Area | Current state |
|---|---|
| Public site | Home page, internship browsing, opportunity details and account entry points. |
| Accounts | Student and employer registration and sign-in flows. Superadmin access is provisioned separately. |
| Applications | Students can submit an application note for a published local listing and view account-level updates. Student accounts can also save listings; recent views are shown on browse/detail pages. |
| Career learning | A career guide with field examples and eight dedicated transferable-skill reading pages. |
| Database | MySQL/MariaDB schema for users, profiles, opportunities, applications and supporting platform records. |
| Superadmin | A permission-guarded overview with basic platform metrics; moderation and broader operator tools are still planned. |
| External listings | An optional Jooble-backed search endpoint. It needs a server-side API key and may need a region-specific endpoint before Ghana search works as intended. |
| Secure messaging | Early Matrix identity-linking and room-validation endpoints. There is no finished chat interface or deployed Matrix homeserver in this repository. |

This is a **prototype under active development**, not a production-ready service. In particular, employer self-service listing management, complete moderation, production operations, external provider configuration, and Matrix chat are unfinished. See [SECURITY_AND_ROADMAP.md](SECURITY_AND_ROADMAP.md) and [SECURE_MESSAGING_ARCHITECTURE.md](SECURE_MESSAGING_ARCHITECTURE.md) for implementation details and known work.

## Technology

- PHP with MySQLi, sessions and server-rendered pages
- MySQL or MariaDB using InnoDB and `utf8mb4`
- Apache (the project is currently set up locally with WampServer)
- Bootstrap/MDB assets and project CSS for the browser interface
- Optional Jooble REST API integration for external opportunity search
- Matrix as the planned protocol for end-to-end encrypted messaging

The project uses a small PHP structure rather than Laravel. There is no Composer or npm setup at this time. PHP 7.4 has been used for local development; before internet-facing deployment, move to a currently supported PHP release and verify the application and its dependencies against it.

## Run locally with WampServer

1. Install and start WampServer with Apache, MySQL/MariaDB, and PHP. Enable the PHP `mysqli` and `curl` extensions; the code also uses MySQL native-driver result support (`mysqli_stmt::get_result()`).
2. Put the repository in WampServer’s web directory, for example:

   ```text
   C:\wamp64\www\internship_mgt
   ```

3. Create the database in phpMyAdmin or the MySQL client:

   ```sql
   CREATE DATABASE internship
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```

4. Configure database access. For a fresh local WampServer installation, `config.php` defaults to database `internship`, user `root`, and an empty password. That default is for local development only. For a different local or hosted database, set `INTERNSHIP_DB_USER` and `INTERNSHIP_DB_PASS` in the environment visible to Apache, or update the connection configuration for your environment. Never commit real credentials.
5. From the repository root, run the schema setup script in a terminal:

   ```bash
   php scripts/setup_internship_schema.php
   ```

   The database must already exist. The script applies `internship.sql` and includes upgrade steps for older local schemas. It is designed not to drop or truncate existing rows; back up an existing database before upgrading it. Legacy application rows with invalid internship references can prevent that particular foreign key from being added until the records are reviewed.
6. Open the local site:

   ```text
   http://localhost/internship_mgt/
   ```

   If you use a different folder or virtual host, update `BASEURL` in `config.php` to match it.

### Optional local demo accounts

After the schema is installed, you can create the repository’s demo accounts from the command line:

```bash
php scripts/create_demo_accounts.php
```

The seeder does not reset an account that already exists. These credentials are for a disposable local database only—never use them on a public deployment.

| Role | Email | Password |
|---|---|---|
| Student | `student.demo@internshiphub.test` | `StudentDemo2026!` |
| Employer | `employer.demo@internshiphub.test` | `EmployerDemo2026!` |
| Superadmin | `superadmin.demo@internshiphub.test` | `SuperAdminDemo2026!` |

The demo seeder creates accounts, not sample internship listings. Do not expect the listing page to show opportunities until published listings exist in the database or external search is configured.

## Optional integrations

### Jooble opportunity search

The external search endpoint is `api/opportunities.php`. It keeps the Jooble key on the server; the key must never be placed in browser JavaScript or committed to the repository. Set `JOOBLE_API_KEY` in the environment used by Apache, then restart Apache. See the [Jooble API portal](https://jooble.org/api/about) and check its current market-specific key and API terms before relying on it. This project currently calls `jooble.org`; a key issued for a different regional Jooble domain requires a matching endpoint change. Without a valid key and supported market endpoint, external results will remain unavailable. Local listings do not require Jooble.

### Matrix secure messaging

The Matrix API files can link a verified Matrix account to a BIT account and check the eligibility and encryption state of a room. A production setup still needs a permanent Matrix server name, an HTTPS homeserver, account provisioning, a browser client built with the Matrix SDK, device verification and recovery flows, backups, and operational monitoring. Matrix accounts use separate credentials from BIT accounts. Do not describe messaging as end-to-end encrypted until the client and homeserver deployment are complete and verified.

## Repository map

```text
api/                    JSON endpoints, including Jooble and Matrix bridges
career-guide.php        Career and internship learning guide
career-skill.php        Allowlisted detail pages for transferable skills
config.php              Local application and environment configuration
core/init.php           Session, database and shared initialization
css/                    Shared and page-specific styles
employer/               Employer area (work in progress)
helpers/helpers.php     Shared authentication, database and formatting helpers
includes/               Public site header and footer
internship.php          Local listings and optional external search interface
internship.sql          Database schema
scripts/                CLI-only schema setup and demo account seeder
student/                Student account pages (work in progress)
widgets/                Saved-opportunity and recent-view components
superAdmin/             Superadmin overview
```

## Security and deployment notes

- Use HTTPS for any deployment with real accounts.
- Use a least-privilege database account outside local development; do not deploy with the default `root` account.
- Store API keys and database credentials in server-side environment configuration or a secrets manager.
- Back up the database and test restoration before upgrades or launch.
- Keep PHP errors out of visitor responses and configure private server-side error logs.
- Review account lifecycle, rate limits, email verification, uploads, employer verification and moderation before opening registration publicly.
- Matrix E2EE depends on client-side encryption, device keys and verification; HTTPS or database encryption alone is not end-to-end encryption.

More detail is in [SECURITY_AND_ROADMAP.md](SECURITY_AND_ROADMAP.md) and [SECURE_MESSAGING_ARCHITECTURE.md](SECURE_MESSAGING_ARCHITECTURE.md).

## Contributing

For proposed work, describe the user need, expected behavior and security/privacy implications. Keep secrets and real user data out of commits. There is no automated test suite configured yet, so document any manual setup or verification needed for a change.

## License

No repository license file has been added yet. Until one is chosen, do not assume the source is licensed for redistribution or commercial reuse.
