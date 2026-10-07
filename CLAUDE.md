# FastAutoReporter

Test-run reporting web app: plain PHP + MariaDB, no framework, no build step. Core idea: **never load data the user didn't ask for** — logs, screenshots and videos are fetched lazily, pages stay light. Keep it that way: no frameworks, no loading whole runs into memory, and **no JS libraries at all** — write small vanilla JS yourself (like `sorttable.js`, which is custom code, not a dependency). No CDN includes, no npm.

## Layout

- Root `*.php` — UI pages (`index.php` run list, `suite.php` run view, `testdetails.php`, `testhistory.php`, `blame.php`, charts, blob/video loaders). See README "Endpoints short description".
- `api/reporter/{run,suite,test}/add.php`, `run/finish.php` — ingestion API used by test frameworks. Calling an endpoint with bad/empty input prints usage help (`show_help()`); keep that behavior when changing payloads.
- `api/reporter/test.pl` — Perl smoke script that posts a run → 2 suites → 4 tests. URLs are hardcoded to `http://localhost/FastAutoReporter/...`; adjust before running.
- `initvar.php` — all app config and feature flags (`$testrailEnabled`, `$jiraEnabled`, `$xrayEnabled`, `$copilotEnabled`, AI prompts, per-repo `$ai_git_projects`).
- `mysqli_connection.php` — `OpenCon()` / `CloseCon()`.
- `reporterdb.sql` — full schema: tables, **stored procedures**, events. The DB logic lives here, not in PHP.
- `copilot_proxy.php`, `fetch_github_code.php` — server-side helpers for the Copilot log analysis; the client logic is JS inside `suite.php` (~line 1160+).
- `stuff/testrail.php` (vendored TestRail API), `SVGGraph/` (git submodule — don't edit).

## How code is written here

- Pages resolve their base path with the `$myreporter = basename(dirname(__FILE__))` block, then `include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php")`. API files use relative includes (`../../../initvar.php`). Follow whichever the neighboring file uses.
- Reads go through stored procedures (`call get_runs(...)`, `get_suit`, `get_test_details`, ...). Queries are built as strings: escape every user value with `mysqli_real_escape_string`, use `NULL` literals for missing optional values. Multi-result procedure calls need `while ($mysqli->next_result()) {;}` before the next query.
- HTML and JS are emitted from PHP (often heredoc/`echo`), PHP config values are injected into JS as literals. Match that style instead of introducing separate bundles.
- New optional integrations get an `$xxxEnabled` flag in `initvar.php` and must render nothing when disabled.
- Indentation is mixed tabs/spaces; match the surrounding lines, don't reformat files.

## Checking changes

- Lint every changed PHP file: `php -l <file>` (CI runs PHP Lint on PRs; there are no unit tests).
- Manual testing happens on each developer's own server — ask for the URL, don't assume one. API smoke test: `perl api/reporter/test.pl` after pointing its URLs at the server.

## Database changes

- Edit `reporterdb.sql` (it's the import source for new installs) **and** provide the `ALTER`/`CREATE PROCEDURE` snippet for existing DBs in the PR description.
- Document new/changed procedures in the README "Procedures" section in the same format.
- Logs can be huge (`max_allowed_packet=1024M`); avoid queries that pull all log rows of a run unless it's an explicit download.

## Secrets

`initvar.php` and `mysqli_connection.php` are committed with **placeholders** (`DBHOST`, `TESTRAILPASS`, `GIT_PAT_WITHPROJECTACCESS`, ...). Never commit real hosts, passwords, tokens or API keys; if a local copy has real values, don't stage those hunks. `.htaccess`/`.htpasswd` are gitignored — don't read or commit them.
