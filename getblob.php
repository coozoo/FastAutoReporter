<?php

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
    $myreporter="";
    }
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php");
    
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts = parse_url($url);
    $logid=null;
    $blob=null;
    $blobtype=null;
    $preview="false";
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['logid'])) {
        $logid=$query['logid'];
    }
    else
    {
    echo "required logid";
    }
    if (isset($query['preview'])) {
        $preview=$query['preview'];
    }
    
 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }


if(isset($query['logid']))
    {
    if($preview=="true")
    {
	$getblobquery="SELECT l_screenshot_preview,l_screenshot_type FROM reporter.log where id=".$logid.";";
    }
    else
    {
	$getblobquery="SELECT l_screenshot_file_name,l_screenshot_type FROM reporter.log where id=".$logid.";";
    }
    //echo($getblobquery);
    if($result = $mysqli->query($getblobquery))
    {
    //var_dump($result);
        while($rows=mysqli_fetch_array($result)){
	    //var_dump($rows);
	    if($rows[0])
	    {
		$blob=$rows[0];
    	    }
        	$blobtype=$rows[1];
	}
        $result->close();
        //$mysqli->next_result();
    }
    }
//var_dump($blobtype);
//var_dump($blob);
//header("Content-type: image/png");
if(is_null($blobtype))
{
    header("Content-type: image/png");
    echo $blob;
}
else
{
    if($preview=="true")
    {
	if(is_null($blob))
	{
	    if(strpos("$blobtype",'text/html') !== false)
	    {
		header("Content-type: image/svg+xml");
		header("X-Blob-Role: ico");
		echo(file_get_contents("img/icons/mime/Crystal-Clear-mime-text-html.svg"));
	    }
		elseif(strpos("$blobtype",'json') !== false)
	    {
		header("Content-type: image/svg+xml");
		header("X-Blob-Role: ico");
		echo(file_get_contents("img/icons/mime/JSON_vector_logo20x20.svg"));
	    }
	    elseif(strpos("$blobtype",'text') !== false)
	    {
		header("Content-type: image/png");
		header("X-Blob-Role: ico");
		echo(file_get_contents("img/icons/mime/Gnome-mime-text.20x20.png"));
	    }
	}
	else
	{
	    header("Content-type: $blobtype");
	    echo $blob;
	}
    }
    else
    {
	header("Content-type: $blobtype");
	echo $blob;
    }
}



//echo '<img src="'.$blob.'" alt="HTML5 Icon" style="width:128px;height:128px">';;


CloseCon($mysqli);

?>
