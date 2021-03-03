# FastAutoReporter

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

Open mysqli_connection.php and assign your values instead of this:

```php
	$dbhost = "DBHOST";
	$dbuser = "DBUSER";
	$dbpass = "DBPASSWORD";
	$db = "DBNAME";
```
#### Testrail configuration

Open gettestrailcase.php find and assign your credentials inside:

```php
    $testrailhost='https://testrail.anzogroup.com/';
    $client = new TestRailAPIClient("$testrailhost");
    $client->set_user('UserAccount');
    $client->set_password('UserPassword');
```
#### Restricted Access

Some file it's better to restrict access by password.

For example `dbrestricted` folder contains `killprocess.php` that allows to kill DB tasks.

To do that create `.htaccess` file:

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

### Paiges short description

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
