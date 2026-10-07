---
name: db-change
description: Change the MariaDB schema, stored procedures or events of the reporter (reporterdb.sql). Use for any new table/column/index/procedure or procedure signature change.
---

# Database change checklist

1. Edit `reporterdb.sql` in place, keeping its phpMyAdmin-export structure (procedures in the `DELIMITER $$` section, tables in `CREATE TABLE IF NOT EXISTS`, indexes/constraints in the `ALTER TABLE` sections at the end). This file is what new installs import.
2. Write a separate migration snippet for existing installs (`ALTER TABLE ...`, `DROP PROCEDURE IF EXISTS ...; CREATE PROCEDURE ...`). Don't commit it as a file unless asked — put it in the PR description / tell the user to run it on their DB.
3. Procedure signature changes break every PHP caller: `grep -rn "call <procedure>" --include=*.php .` and update all of them. Prefer a new versioned procedure (like `add_test_v2`) when the API must stay compatible.
4. Size awareness: `log` holds every log line and screenshot blobs. Large binary data belongs in its own table/column fetched lazily (see `getblob.php`), never in columns selected by list views. Consider `delete_old_logs` / `delete_old_runs` cleanup for any new per-test data.
5. Update the README "DB Structure" → "Procedures"/"Events" sections in the existing `<details>` format with parameters and example calls.
