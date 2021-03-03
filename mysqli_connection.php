<?php
    function OpenCon()
    {
	$dbhost = "DBHOST";
	$dbuser = "DBUSER";
	$dbpass = "DBPASSWORD";
	$db = "DBNAME";
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass,$db);
	
	if($mysqli->connect_errno)
	{
	    printf("Connect failed: %s\n", $mysqli->connect_errno.":".$mysqli->connect_error);
	    exit();
	}
	
	//printf("Initial character set: %s\n", $mysqli->character_set_name());
	
	if(!$mysqli->set_charset("utf8"))
	{
	    printf("Error loading character set utf8: %s\n", $mysqli->error);
	    exit();
	}
	//else
	//{
	//    printf("Current character set: %s\n", $mysqli->character_set_name());
	//}
	return $mysqli;
    }

    function CloseCon($mysqli)
    {
	$mysqli -> close();
    }
?>
