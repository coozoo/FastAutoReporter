# Fast Automation Reporter

Test reporting system. Fast and it's not ugly.

Why it was created? Because everything that is available usually based on "latest technologies", pages of such reporters usually overloaded with javascript,they loading reports and manipulating with them on the fly in memory, as result such systems usually pretty nifty and pretty slow even when working with small reports and absolutelly unusable with report more than few dozens MB.

This reporter based on php and mysql. The main strategy do not load data when you don't need it.

Using it you can build charts based on stat and view this stat, you can view report logs and it is loaded only when you really want to view it and still there is possibility to load report as file.

There is some integration with testrail you can preview and navigate to suite/testcase.

Start page with list of runs:

<img src="https://user-images.githubusercontent.com/25594311/144766609-e9e10633-dff3-44b3-a044-52aeed24218f.png" width="100%"></img> 

Run example:

<img src="https://user-images.githubusercontent.com/25594311/144766701-4cdc91f9-3e16-43b4-8804-815ee00a6b99.png" width="100%"></img> 


## Installation

### Download sources

```bash
$ git clone https://github.com/coozoo/FastAutoReporter
$ cd FastAutoReporter
$ git submodule update --init --recursive
```
Copy it to your webserver directory

### Dependencies

Additionally you can update testrail API if required from here 

https://github.com/gurock/testrail-api/tree/master/php

And replace `stuff/testrail.php` with a new one.

### Configuration

Currently there is no one place config.

#### DB configuration

Open `mysqli_connection.php` and assign your values instead of this:

```php
	$dbhost = "DBHOST";
	$dbuser = "DBUSER";
	$dbpass = "DBPASSWORD";
	$db = "DBNAME";
```
#### Testrail configuration

Open `initvar.php` find and assign your credentials inside:

```php
$testrailhost='https://testrail.com/';
$testrailuser='TESTRAILUSER';
$testrailpass='TESTRAILPASS';
```
#### Restricted Access

For some files it's better to restrict access by password.

For example `dbrestricted` folder contains `killprocess.php` that allows to kill DB tasks sure it's better to protect it by password.

To do that you need to create `.htaccess` file (example):

```bash
$ cat .htaccess 
Authtype Basic
AuthName "Password Protected"
AuthUserFile /var/www/html/myreporter/dbrestricted/.htpasswd
Require valid-user
```
You can add user to `.htpasswd` by:
```bash
$ htpasswd /var/www/html/myreporter/dbrestricted/.htpasswd newuser
```

### API for adding data into DB

Current API implemented in a bit strange way for compatibility with old java service.

Each endpoint will return short info how to call it just call this page from browser.

./api/reporter/run/add.php - add new run;

./api/reporter/run/finish.php - mark run as finished

./api/reporter/suite/add.php - add suite;

./api/reporter/test/add.php - add test and logs;

For compatibility with old java service it can be added .htaccess file inside ./api/reporter folder to hide php extensions:

```bash
$ cat ./api/reporter/.htaccess 
RewriteRule ^([^.?]+)$ %{REQUEST_URI}.php [L]

# Return 404 for php
RewriteCond %{THE_REQUEST} "^[^ ]* .*?\.php[? ].*$"
RewriteRule .* - [L,R=404]

```


### Enpoints short description

**blame.php** - view that represents most fails and who is responsible for tests;

**dbprocesslist.php** - list current DB tasks;

**downloadrunlogs.php** - download zip file with all logs;

**feature.php** - simply call suite.php in another view mode;

**getblob.php** - blob loader from DB;

**getrunstatus.php** - status of test run;

**gettestrailcase.php** - preview testrail case;

**index.php** - main page with filters;

**mysqli_connection.php** - mysql connector;

**runs.php** - list of tables;

**statuspiechart.php** - pie chart with amount of success, fails for suite view;

**suite.php** - main view to see results of testrun;

**systeminfo.php** - DB info and task manager;

**testdetails.php** - info about test;

**testhistory.php** - show history of some test case;

**testresultschart.php**  - this will build stats for filtered test runs (accept post parameters only);

**testvideo.php** - video loader.

## DB Setup

MariaDB prefarable.

<details>
  <summary>
    Merge this difference to your MariaDB my.ini file
    Just keep in mind it's just approximate settings. It's possible that you require less or more memory (really I don't think that you need more. Our reports pretty heavy and it's fast enough) for some them everything depends on amount of your data.
    And don't forget to enable events `event_scheduler=ON` it's important for autofinish runs in case if your system crashed and unable to call run finish endpoint. As well it is used for cleaning purposes to remove old logs and test runs from DB (see [Events](#events) section below).
  </summary>

```ini
	
[client]
default-character-set=utf8mb4
	
[mysqld]
key_buffer=16M
max_allowed_packet=1024M
sort_buffer_size=512K
net_buffer_length=8K
read_buffer_size=256K
read_rnd_buffer_size=512K
event_scheduler=ON

## You can set .._buffer_pool_size up to 50 - 80 %
## of RAM but beware of setting memory usage too high
innodb_buffer_pool_size=2048M
## Set .._log_file_size to 25 % of buffer pool size
innodb_log_file_size=5M
innodb_log_buffer_size=8M
innodb_flush_log_at_trx_commit=1
innodb_lock_wait_timeout=50

character-set-server=utf8mb4
collation-server=utf8mb4_general_ci
	
[mysqldump]
max_allowed_packet=16M

```
</details>

## DB Initialization

Use phpmyadmin to import tables, procedures and events (all objects important for functionality)

reporterdb.sql - contains everything what you need to import

## DB Structure

### Tables

<img src="https://user-images.githubusercontent.com/25594311/109930953-e6abe980-7cd0-11eb-93ea-825ab3d43be7.png" width="100%"></img>

### Procedures

Those procedures are very important part of frontend reporter.

Here list of procedures used in project, purpose and examples how to call them:

<details>
  <summary>
    <b>close_running</b> - procedure that estimates common run time for test run and close test run if time is exceeded.<br>
  </summary>
  Procedure doesn't require any parameters
</details>

<details>
  <summary>
    <b>count_runs</b> - procedure returns currently visible amount of test runs.<br>
  </summary>
  All procedure parameters the same as for <i>get_runs</i> (basically it would be good to merge this precedures and return sets but in such case it is required to change php code a bit).
</details>

<details>
  <summary>
    <b>delete_old_logs</b> - procedure will delete old logs.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>number_of_days</i> INT</b> - number of days, everything older than this amount of days will be deleted<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call delete_old_logs(30)</i> - delete logs older than 30 days<br>
</details>

<details>
  <summary>
    <b>delete_old_runs</b> - procedure will delete old test runs.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>number_of_days</i> INT</b> - number of days, everything older than this amount of days will be deleted<br>
       <b>IN <i>is_dev_run</i> BOOL</b> - type of runs to delete;<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call delete_old_runs(7,true)</i> - delete dev test tuns older than 7 days<br>
</details>

<details>
  <summary>
    <b>get_blamed</b> - procedure returns stats about amount of test cases by developer.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>days</i> INT</b> - number of days, will gather stat for such amount of days;<br>
       <b>IN <i>statuses</i> TEXT</b> - statuses list separated by comma (FAIL,ERROR,PASS,SKIP);<br>
       <b>IN <i>who</i> TEXT</b> - developer names separated by comma;<br>
       <b>IN <i>teamid</i> INT</b> - team ID, not used in future possible to get stats by teamid;<br>
       <b>IN <i>runid</i> INT</b> - run ID, get stats for specified run;<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call get_blamed(1,'FAIL,ERROR',NULL,NULL,3841)</i> - returns stats of failed test cases by dev for test run 3841<br>
       <i>call get_blamed(1,'FAIL,ERROR',NULL,NULL,NULL)</i> - returns stats of failed test cases by dev for one 1 since now<br>
       <i>call get_blamed(1,'FAIL,ERROR','Developer Name',NULL,3841)</i> - returns stats of failed test cases just for one dev for test run 3841<br>
       <i>call get_blamed(1,'FAIL,ERROR','First DevName,Second DevName',NULL,3841)</i> - returns stats of failed test cases by devs just for two devs in test run 3841<br>
</details>

<details>
  <summary>
    <b>get_feature</b> - procedure returns tests list for run by feature.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>RUNID</i> INT</b> - get tests by run ID;<br>
       <b>IN <i>UUID</i> VARCHAR(255)</b> - get tests by run UUID (for compatibility with Java part);<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call get_feature(3841,NULL)</i> - returns list of testcases by run ID<br>
       <i>call get_feature(NULL,'6a6b304f-6ca7-46c4-a6aa-305258924706')</i> - returns list of testcases by run UUID<br>
</details>

<details>
  <summary>
    <b>get_runs</b> - procedure returns all runs filtered by criterias.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>userenv</i> VARCHAR(255)</b> - environment name;<br>
       <b>IN <i>limitstartrow</i> INT</b> - filter out rows before from (used for pagination);<br>
       <b>IN <i>limitnumberofrows</i> INT</b> - filter out rows after this (used for pagination);<br>
       <b>IN <i>testtypeid</i> INT</b> - test type ID (Backend, Frontend);<br>
       <b>IN <i>teamid</i> INT</b> - filter by team id;<br>
       <b>IN <i>featureid</i> INT</b> - not used (and I suppose it is bad idea to use it);<br>
       <b>IN <i>startdate</i> DATETIME</b> - filter out everything before this date;<br>
       <b>IN <i>enddate</i> DATETIME</b> - filter out everything after this date;<br>
       <b>IN <i>isdevrun</i> BOOL</b> - include dev runs tor result;<br>
       <b>IN <i>equalrunname</i> VARCHAR(255)</b> - filter by exact run name;<br>
       <b>IN <i>likerunname</i> VARCHAR(255)</b> - filter by run names that contains value;<br>
       <b>IN <i>equalversion</i> LONGTEXT</b> - filter by exact version;<br>
       <b>IN <i>likeversion</i> LONGTEXT</b> - filter by part of version;<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call get_runs(NULL,0,30,NULL,NULL,NULL,'2021-01-13 08:23',NULL,false,NULL,NULL,'','');</i> - filter by start date and return  only 30 rows<br>
       <i>call get_runs('DEV',30,60,NULL,NULL,NULL,'2021-01-13 08:23',2021-02-13 08:23,false,NULL,NULL,'','');</i> - filter results between date range by environment and return  30 items after first 30<br>
       <i>call get_runs(NULL,0,30,1,1,NULL,'2021-01-13 08:23',NULL,true,NULL,NULL,'','');</i> - return results including dev runs filtered by test type ID and teamid<br>
       <i>call get_runs(NULL,0,30,1,1,NULL,'2021-01-13 08:23',NULL,true,NULL,'LBA_DEV_Functional','','')</i> - filter by part of run name<br>
</details>

<details>
  <summary>
    <b>get_run_details</b> - procedure to get brief info about run.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>RUNID</i> INT</b> - get tests by run ID;<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call get_run_details(3841);</i> - get run details by run ID<br>
</details>

<details>
  <summary>
    <b>get_suit</b> - procedure to get detailed info by suits.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>RUNID</i> INT</b> - get tests by run ID;<br>
       <b>IN <i>UUID</i> VARCHAR(255)</b> - get tests by run UUID (for compatibility with Java part);<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call get_suit(3841,NULL)</i> - returns suits by run ID<br>
       <i>call get_suit(NULL,'6a6b304f-6ca7-46c4-a6aa-305258924706')</i> - returns suits by run UUID<br>
</details>

<details>
  <summary>
    <b>get_test_details</b> - procedure to return list of logs for test case.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>TESTID</i> INT</b> - get logs by test ID;<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call get_test_details(169222)</i> - returns logs by test ID<br>
</details>

<details>
  <summary>
    <b>get_test_history</b> - procedure to return test case execution history.<br>
  </summary>
  <br><b><i><u>Parameters:</u></i></b><br>
       <b>IN <i>RUNNAME</i> VARCHAR(255)</b> - test run name;<br>
       <b>IN <i>TESTNAME</i> VARCHAR(255)</b> - test case name;<br>
       <b>IN <i>startdate</i> DATETIME</b> - date from;<br>
       <b>IN <i>enddate</i> DATETIME</b> - date till;<br>
  <br><b><i><u>Examples:</u></i></b><br>
       <i>call get_test_history('LBA_DEV_Functional','successfulLoginLBA','2021-01-13 08:23',NULL)</i> - returns execution history by names from date<br>
</details>

### Events

Events important for DB maintanance and some frontend functionality.

**close_runs_in_progress** - calls `close_running` procedure every minute to update test run executiion state.

**remove_old_dev_runs** - maintanance event to clear dev test runs it calls `delete_old_runs` procedure.

**remove_old_logs** - maintanance event to clear old logs it calls `delete_old_logs` procedure.

