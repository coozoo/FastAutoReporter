---
name: add-api-endpoint
description: Add or extend an ingestion API endpoint under api/reporter/ (new endpoint, new payload field). Use when the task touches how test frameworks push runs/suites/tests/logs into the reporter.
---

# Adding / changing an ingestion endpoint

1. Copy the structure of the closest existing endpoint (`api/reporter/run/add.php` for simple JSON → one procedure call, `api/reporter/test/add.php` for payloads with arrays/blobs):
   - `$myreporter` block + relative `include("../../../initvar.php")` and `mysqli_connection.php`
   - `header('Content-Type: application/json')`, read `php://input`, `json_decode(..., true)`
   - invalid JSON → print error + `show_help()` + `http_response_code(415)`
   - missing required fields → `"Error: insufficient data"` + `show_help()` + `406`
   - DB error or `MYSQL_ERROR` in the procedure result → print it + `500`
2. Update `show_help()` with a full example payload and an INFO block describing every new field. This help text is the API documentation.
3. Optional fields must be passed as SQL `NULL` when absent; escape every string with `mysqli_real_escape_string`. Base64 payloads are `base64_decode`d before insert.
4. Stay backward compatible — existing Java/Perl clients send the old payloads. New fields are optional unless the user says otherwise.
5. If a new procedure/column is needed, follow the `db-change` skill.
6. Add the endpoint to the README "API for adding data into DB" list, and extend `api/reporter/test.pl` with a sample request.
7. `php -l` the changed files.
