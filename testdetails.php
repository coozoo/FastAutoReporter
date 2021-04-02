<?php
    //error_reporting(E_ALL);
    //ini_set('display_errors', 1);
    basename($_SERVER['DOCUMENT_ROOT']);
    $myreporter=basename(dirname(__FILE__));
    if(basename($_SERVER['DOCUMENT_ROOT'])==$myreporter)
    {
	$myreporter="";
    }
$_script_started = microtime(1);
    include($_SERVER['DOCUMENT_ROOT']."/$myreporter/initvar.php");
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts=parse_url($url);
    
    $testid="NULL";
    $status="";
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['testid'])) {
        $testid=$query['testid'];
    }
    if (isset($query['status'])) {
        $status=$query['status'];
    }

$cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
                echo("<!DOCTYPE html><html lang=\"en\"><head><title>Test Details</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
        	<script src=\"sorttable.js\" type=\"text/javascript\"></script>
        	    </head><body  onkeypress=\"return onkeypress_body(event);\">");
 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
    
$resultmetacountcolname="metaamount";
$resultmetacount=0;
$logtable="<table id=\"logtable\" class=\"blueTable\">";
$logtablebody="<tbody>";
$logtablefirstcolname="logid";
//call get_test_details(testid)
    $getquery="call get_test_details($testid);";
    //echo $getquery;
    if (!$mysqli->multi_query($getquery)) {
        echo "Multi query failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }
    do {
	$trid=0;
	$currentfirstcolname="";
	if ($result = $mysqli->store_result()) {
            //var_dump($result->fetch_all(MYSQLI_ASSOC));
            //do that for each table row
            while ($rows=mysqli_fetch_array($result)) {
                $finfo = $result->fetch_fields();
                $tcol=0;
                $logid=0;
                $logtablerow="";
                foreach ($finfo as $val) {
                    if ($tcol==0) {
                        $currentfirstcolname=$val->name;
			// add new row by type of table
            		if ($currentfirstcolname==$logtablefirstcolname) {
                	    $logtablerow.="<tr id=\"logrow_{logid}\">";
            		}
            	    }
            	    if ($currentfirstcolname==$resultmetacountcolname) {
            		$resultmetacount=$rows[$val->name];
            	    }
            	    elseif ($currentfirstcolname==$logtablefirstcolname) {
            	    if($val->name=="logid")
                        {
                            $logid=$rows[$val->name];
                            $logtablebody.=str_replace("{logid}",$logid,$logtablerow);
                    	}
                    	elseif($val->name=="meta" && $resultmetacount>0 && $rows[$val->name]!=null)
                    	{
                    	    //$logtablebody.="<td><a href=\"getblob.php?logid=$logid\" target=\"_blank\">Open Image</a></td>";
                    	    $logtablebody.="<td style=\"width:1%;text-align:center;\"><a href=\"getblob.php?logid=$logid\" target=\"_blank\"><img src=\"getblob.php?logid=$logid&preview=true\" title=\"Open Image\" alt=\"Open Image\"></a></td>";
			    //$logtablebody.="<td><a href=\"getblob.php?logid=$logid\" target=\"_blank\"><img src=\"getblob.php?logid=$logid\" style=\"width:500px; height:20px\" title=\"Open Image\" alt=\"Open Image\"></a></td>";
									
                    	}
			elseif($val->name=="meta" && $resultmetacount>0)
			{
			    $logtablebody.="<td style=\"text-align:center\">--</td>";
			}
                    	elseif($val->name!="meta")
                    	{
                    	    $logrowvalue=htmlspecialchars($rows[$val->name]);
			    if(strpos($logrowvalue,"[")== 0)
			    {
				$pattern = '/\[(FONT-WEIGHT[^\]]+)\]/';
				preg_match($pattern, $logrowvalue, $matches);
				$logrowvalue=preg_replace ($pattern,"", $logrowvalue);
				//print_r($matches);
				//print($matches[1]);
				$logrowvalue="<font style=\"$matches[1];\">".$logrowvalue."</font>";
			    }
			    /*if(strpos($logrowvalue,"[INFO]")=== false)
			    {
				if(strpos($logrowvalue,"[ERROR]")!== false || strpos($logrowvalue,"[FAIL]")!== false)
				{
                    		    $logrowvalue="<font style=\"font-weight:bold;color:#a84428;\">".$logrowvalue."</font>";
				}
				else if(strpos($logrowvalue,"[WARNING]")!== false)
				{
				    $logrowvalue="<font style=\"font-weight:bold;color:#ff54e8;\">".$logrowvalue."</font>";
				}
				elseif(strpos($logrowvalue,"[PASS]")!== false)
				{
				    $logrowvalue="<font style=\"font-weight:bold;color:green;\">".$logrowvalue."</font>";
				}
				elseif(strpos($logrowvalue,"[SKIP]")!== false)
				{
				    $logrowvalue="<font style=\"font-weight:bold;\">".$logrowvalue."</font>";
				}
				elseif(strpos($logrowvalue,"[INFOMARKOUT]")!== false)
				{
				    $logrowvalue="<font style=\"font-weight:bold;color:blue;\">".$logrowvalue."</font>";
				}
			    }*/
			    /*if($status=="FAIL" || $status=="ERROR")
			    {
				if(strpos($logrowvalue,"[ERROR]")!== false || strpos($logrowvalue,"[FAIL]")!== false)
				{
                    		    $logrowvalue="<font style=\"font-weight:bold;color:#a84428;\">".$logrowvalue."</font>";
				}
				else if(strpos($logrowvalue,"[WARNING]")!== false)
				{
				    $logrowvalue="<font style=\"font-weight:bold;color:#ff54e8;\">".$logrowvalue."</font>";
				}
				elseif(strpos($logrowvalue,"[PASS]")!== false)
				{
				    $logrowvalue="<font style=\"font-weight:bold;color:green;\">".$logrowvalue."</font>";
				}
			    }
			    else
			    {	
				if(strpos($logrowvalue,"[WARNING]")!== false)
				{
				    $logrowvalue="<font style=\"font-weight:bold;color:#ff54e8;\">".$logrowvalue."</font>";
				}
				elseif(strpos($logrowvalue,"[PASS]")!== false)
				    {
					$logrowvalue="<font style=\"font-weight:bold;color:green;\">".$logrowvalue."</font>";
				    }
			    }*/
			    //$logtablebody=$logtablebody."<td><code style=\"white-space:pre;word-break:normal;word-wrap:normal;\">".$logrowvalue."</code></td>";

			    $logtablebody.="<td><code style=\"white-space:pre;word-break:normal;word-wrap:normal;\">".$logrowvalue."</code></td>";
            		}
            		}
            	    $tcol++;
            	    }
            	    if ($currentfirstcolname==$logtablefirstcolname) {
                	    $logtablebody.="</tr>";
            		}
            	    
                }
	    $result->free();
        }
        $trid++;
    } while ($mysqli->more_results() && $mysqli->next_result());

    $logtable.=$logtablebody;
    $logtable.="</tbody></table>";
//    echo("requested test details $testid<br>");
//    echo("Page under construction");
    echo $logtable;
    echo ("<script  type=\"text/javascript\">
function onkeypress_body(event)
{
    if (event.keyCode == 13 || event.which == 13){
        window.scrollTo(0,0);
    }
}

</script>");
$_page_time_seconds = microtime(1) - $_script_started;
if( isset($_GET['debug_mode']) ) {
    echo $_page_time_seconds;
}
    echo("</body>
</html>");
            
CloseCon($mysqli);
/*
For Inserting into DB

$db = mysqli_connect("localhost","root","","DbName"); //keep your db name
$image = addslashes(file_get_contents($_FILES['images']['tmp_name']));
//you keep your column name setting for insertion. I keep image type Blob.
$query = "INSERT INTO products (id,image) VALUES('','$image')";  
$qry = mysqli_query($db, $query);


For Accessing image From Blob

$db = mysqli_connect("localhost","root","","DbName"); //keep your db name
$sql = "SELECT * FROM products WHERE id = $id";
$sth = $db->query($sql);
$result=mysqli_fetch_array($sth);
echo '<img src="data:image/jpeg;base64,'.base64_encode( $result['image'] ).'"/>';
*/

?>
