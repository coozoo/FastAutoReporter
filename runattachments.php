<?php
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php");
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $parts=parse_url($url);

    $runid="NULL";

    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['runid'])) {
        $runid=$query['runid'];
    }

function humansize($bytes)
{
    if($bytes>=1048576)
    {
	return round($bytes/1048576,1)." MB";
    }
    if($bytes>=1024)
    {
	return round($bytes/1024,1)." KB";
    }
    return $bytes." B";
}

$cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
                echo("<!DOCTYPE html><html lang=\"en\"><head><title>Run Attachments</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
        	    </head><body>");

if(!ctype_digit((string)$runid))
{
    echo "required numeric runid</body></html>";
    exit;
}

 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }

$attachmentstable="<table id=\"attachmentstable\" class=\"greyGridTable\"><thead><tr><th>File</th><th>Type</th><th>Size</th><th>Added</th><th></th></tr></thead><tbody>";
    if($result = $mysqli->query("call get_run_attachments_list($runid);"))
    {
	while($rows=mysqli_fetch_assoc($result))
	{
	    $attachmentid=$rows['attachmentid'];
	    // file names and types come from api clients, escape them
	    $filename=htmlspecialchars($rows['filename']);
	    $contenttype=htmlspecialchars((string)$rows['contenttype']);
	    $attachmentstable.="<tr id=\"attachmentrow_$attachmentid\">".
			    "<td><a href=\"getattachment.php?attachmentid=$attachmentid\" target=\"_blank\" title=\"Open\">$filename</a></td>".
			    "<td>$contenttype</td>".
			    "<td value=\"".$rows['filesize']."\">".humansize($rows['filesize'])."</td>".
			    "<td>".$rows['added']."</td>".
			    "<td><a href=\"getattachment.php?attachmentid=$attachmentid&download=true\">".
				"<img src=\"img/icons/Gnome-document-save.svg\" style=\"width:20px; height:20px\" title=\"Download\" alt=\"Download\"></a></td>".
			    "</tr>";
	}
	$result->close();
	while($mysqli->more_results() && $mysqli->next_result()) {;}
    }
    else
    {
	echo "Error: ".$mysqli->error;
    }
$attachmentstable.="</tbody></table>";

echo $attachmentstable;
echo "</body></html>";

CloseCon($mysqli);
?>
