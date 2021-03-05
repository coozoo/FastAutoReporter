# Fast Automation Reporter

## Installation

### Download sources
```bash
$ git clone https://github.com/coozoo/FastAutoReporter
```
Copy it to your webserver directory

### Dependencies

You need to install SVGGraph

```bash
$ cd FastAutoReporter
$ git clone https://github.com/goat1000/SVGGraph
```

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

Open `gettestrailcase.php` find and assign your credentials inside:

```php
    $testrailhost='https://url.com/';
    $client = new TestRailAPIClient("$testrailhost");
    $client->set_user('UserAccount');
    $client->set_password('UserPassword');
```
#### Restricted Access

For some files it's better to restrict access by password.

For example `dbrestricted` folder contains `killprocess.php` that allows to kill DB tasks sure it's better to protect it by password.

To do that you need to create `.htaccess` file:

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

### Pages short description

**blame.php** - view that represents most fails and who is responsible for tests;

**dbprocesslist.php** - list current DB tasks;

**downloadrunlogs.php** - download zip file with all logs;

**feature.php** - simply call suite.php in another view mode;

**getblob.php** - image loader;

**getrunstatus.php** - status of test run;

**gettestrailcase.php** - preview testrail case;

**index.php** - main page with filters;

**mysqli_connection.php** - mysql connector;

**runs.php** - list of tables;

**statuspiechart.php** - pie chart with amount of success, fails for suite view;

**suite.php** - maine view to see results of testrun;

**systeminfo.php** - DB info and task manager;

**testdetails.php** - info about test;

**testhistory.php** - show history for some test case;

**testresultschart.php**  - this will beld stats for filtered test runs (accept post parameters only);

**testvideo.php** - video loader.

## DB Setup

MariaDB prefarable.

<details>
  <summary>
    Merge this difference to your MariaDB my.ini file
  </summary>

```ini
# Example MySQL config file for small systems.
#
# This is for a system with little memory (<= 64M) where MySQL is only used
# from time to time and it's important that the mysqld daemon
# doesn't use much resources.
#
# You can copy this file to
# C:/xampp/mysql/bin/my.cnf to set global options,
# mysql-data-dir/my.cnf to set server-specific options (in this
# installation this directory is C:/xampp/mysql/data) or
# ~/.my.cnf to set user-specific options.
#
# In this file, you can use all long options that a program supports.
# If you want to know which options a program supports, run the program
# with the "--help" option.

# The following options will be passed to all MySQL clients
[client]
# password       = your_password 
port=3306
socket="C:/xampp/mysql/mysql.sock"


# Here follows entries for some specific programs 

# The MySQL server
default-character-set=utf8mb4
[mysqld]
port=3306
socket="C:/xampp/mysql/mysql.sock"
basedir="C:/xampp/mysql"
tmpdir="C:/xampp/tmp"
datadir="C:/xampp/mysql/data"
pid_file="mysql.pid"
# enable-named-pipe
key_buffer=16M
max_allowed_packet=1024M
sort_buffer_size=512K
net_buffer_length=8K
read_buffer_size=256K
read_rnd_buffer_size=512K
myisam_sort_buffer_size=8M
log_error="mysql_error.log"
event_scheduler=ON

# Change here for bind listening
#bind-address="127.0.0.1" 
bind-address=0.0.0.0
# bind-address = ::1          # for ipv6

# Where do all the plugins live
plugin_dir="C:/xampp/mysql/lib/plugin/"

# Don't listen on a TCP/IP port at all. This can be a security enhancement,
# if all processes that need to connect to mysqld run on the same host.
# All interaction with mysqld must be made via Unix sockets or named pipes.
# Note that using this option without enabling named pipes on Windows
# (via the "enable-named-pipe" option) will render mysqld useless!
# 
# commented in by lampp security
#skip-networking
#skip-federated

# Replication Master Server (default)
# binary logging is required for replication
# log-bin deactivated by default since XAMPP 1.4.11
#log-bin=mysql-bin

# required unique id between 1 and 2^32 - 1
# defaults to 1 if master-host is not set
# but will not function as a master if omitted
server-id	=1

# Replication Slave (comment out master section to use this)
#
# To configure this host as a replication slave, you can choose between
# two methods :
#
# 1) Use the CHANGE MASTER TO command (fully described in our manual) -
#    the syntax is:
#
#    CHANGE MASTER TO MASTER_HOST=<host>, MASTER_PORT=<port>,
#    MASTER_USER=<user>, MASTER_PASSWORD=<password> ;
#
#    where you replace <host>, <user>, <password> by quoted strings and
#    <port> by the master's port number (3306 by default).
#
#    Example:
#
#    CHANGE MASTER TO MASTER_HOST='125.564.12.1', MASTER_PORT=3306,
#    MASTER_USER='joe', MASTER_PASSWORD='secret';
#
# OR
#
# 2) Set the variables below. However, in case you choose this method, then
#    start replication for the first time (even unsuccessfully, for example
#    if you mistyped the password in master-password and the slave fails to
#    connect), the slave will create a master.info file, and any later
#    change in this file to the variables' values below will be ignored and
#    overridden by the content of the master.info file, unless you shutdown
#    the slave server, delete master.info and restart the slaver server.
#    For that reason, you may want to leave the lines below untouched
#    (commented) and instead use CHANGE MASTER TO (see above)
#
# required unique id between 2 and 2^32 - 1
# (and different from the master)
# defaults to 2 if master-host is set
# but will not function as a slave if omitted
#server-id       = 2
#
# The replication master for this slave - required
#master-host     =   <hostname>
#
# The username the slave will use for authentication when connecting
# to the master - required
#master-user     =   <username>
#
# The password the slave will authenticate with when connecting to
# the master - required
#master-password =   <password>
#
# The port the master is listening on.
# optional - defaults to 3306
#master-port     =  <port>
#
# binary logging - not required for slaves, but recommended
#log-bin=mysql-bin


# Point the following paths to different dedicated disks
#tmpdir = "C:/xampp/tmp"
#log-update = /path-to-dedicated-directory/hostname

# Uncomment the following if you are using BDB tables
#bdb_cache_size = 4M
#bdb_max_lock = 10000

# Comment the following if you are using InnoDB tables
#skip-innodb
innodb_data_home_dir="C:/xampp/mysql/data"
innodb_data_file_path=ibdata1:10M:autoextend
innodb_log_group_home_dir="C:/xampp/mysql/data"
#innodb_log_arch_dir = "C:/xampp/mysql/data"
## You can set .._buffer_pool_size up to 50 - 80 %
## of RAM but beware of setting memory usage too high
innodb_buffer_pool_size=2048M
## Set .._log_file_size to 25 % of buffer pool size
innodb_log_file_size=5M
innodb_log_buffer_size=8M
innodb_flush_log_at_trx_commit=1
innodb_lock_wait_timeout=50

## UTF 8 Settings
#init-connect=\'SET NAMES utf8\'
#collation_server=utf8_unicode_ci
#character_set_server=utf8
#skip-character-set-client-handshake
#character_sets-dir="C:/xampp/mysql/share/charsets"
sql_mode=NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION
log_bin_trust_function_creators=1

character-set-server=utf8mb4
collation-server=utf8mb4_general_ci
[mysqldump]
max_allowed_packet=16M

[mysql]
# Remove the next comment character if you are not familiar with SQL
#safe-updates

[isamchk]
key_buffer=20M
sort_buffer_size=20M
read_buffer=2M
write_buffer=2M

[myisamchk]
key_buffer=20M
sort_buffer_size=20M
read_buffer=2M
write_buffer=2M

[mysqlhotcopy]

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

