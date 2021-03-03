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

Open gettestrailcase.php find and assign you credentials inside:

```php
    $testrailhost='https://testrail.anzogroup.com/';
    $client = new TestRailAPIClient("$testrailhost");
    $client->set_user('UserAccount');
    $client->set_password('UserPassword');
```

