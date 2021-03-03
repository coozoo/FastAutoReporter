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
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts=parse_url($url);
    
    $runname="NULL";
    $runnameparam="NULL";
    $testname="NULL";
    $testnameparam="NULL";
    $environment="ALL";
    
    $startdate="NULL";
    $enddate="NULL";
    $startdate_param=$startdate;
    $enddate_param=$enddate;
    $starttime="NULL";
    $endtime="NULL";

    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['runname'])) {
	$runnameparam=$query['runname'];
        $runname="'".$query['runname']."'";
    }
    if (isset($query['testname'])) {
	$testnameparam=$query['testname'];
        $testname="'".$query['testname']."'";
    }
    
    if(isset($query['startdate']))
    {
	$startdate=$query['startdate'];
	$startdate_param=$startdate;
	if(isset($query['starttime']) && $startdate!="NULL")
	{
	    $starttime=$query['starttime'];
	    $startdate.=" ".str_replace("_",":",$starttime);
	}
	if($startdate!="NULL")
	{
	    $startdate="'".$startdate."'";
	}
    }
    //echo $startdate;
    if(isset($query['enddate']))
    {
	$enddate=$query['enddate'];
	$enddate_param=$enddate;
	if(isset($query['endtime']) && $enddate!="NULL")
	{
	    $endtime=$query['endtime'];
	    $enddate.=" ".str_replace("_",":",$endtime);
	}
	if($enddate!="NULL")
	{
	    $enddate="'".$enddate."'";
	}
    }
    //echo $enddate;

    $envselection="&nbsp;<b>Environment:</b><select id=\"envselection\">";
    if(isset($query['environment']))
    {
	$environment=$query['environment'];
	$envselection.="<option value=\"ALL\">All</option>";
    }
    else
    {
	$envselection.="<option value=\"ALL\" selected=\"selected\">All</option>";
    }

$cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
            echo("<!DOCTYPE html><html lang=\"en\"><head><title>Test History</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
        	<script src=\"sorttable.js\" type=\"text/javascript\"></script>
        	    </head><body>");
// include($_SERVER['DOCUMENT_ROOT']."/$myreporter/header.php");
// include($_SERVER['DOCUMENT_ROOT']."/$myreporter/datetimeform.php");
 include($_SERVER['DOCUMENT_ROOT']."/$myreporter/mysqli_connection.php");
    $mysqli = OpenCon();
    if (!isset($mysqli)) {
        echo "Connection failed";
    }
    
$runtable="<table id=\"runtable\" class=\"blueTable\">";
$runtableheader="<thead id=\"runheader\"><tr>";
$runtablebody="<tbody>";
$runtablefirstcolname="RunID";
    $getquery="call get_test_history($runname,$testname,$startdate,$enddate);";
    //echo $getquery;
    if (!$mysqli->multi_query($getquery)) {
        echo "Multi query failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }
    
    $startdatesdata=array();
    $colorsdata=array();
    $linksdata=array();
    $envoptions=array();
    do {
	$trid=0;
	$currentfirstcolname="";
	if ($result = $mysqli->store_result()) {
            //var_dump($result->fetch_all(MYSQLI_ASSOC));
            //do that for each table row
            while ($rows=mysqli_fetch_array($result)) {
                $finfo = $result->fetch_fields();
                $tcol=0;
                $runid=0;
                $testid=0;
                $runtablerow="";
		$runtestlink="";
		$teststartdate="";
		$testresult="";
		$curenv="";
		array_push($envoptions,$rows["EnvName"]);
		if($rows["EnvName"]==$environment || $environment=="ALL")
            	    {
                foreach ($finfo as $val) {
            	    
            	    
            	    if($trid==0)
            	    {
            		if($val->name!="RunID" && $val->name!="TestID")
            		{
            		    $runtableheader.="<th>".$val->name."</th>";
            		}
            	    
            	    }
                    if ($tcol==0) {
                        $currentfirstcolname=$val->name;
			// add new row by type of table
            		if ($currentfirstcolname==$runtablefirstcolname) {
                	    $runtablerow.="<tr id=\"runid_{runid}\">";
            		}
            	    }
            	    if ($currentfirstcolname==$runtablefirstcolname) {
            		switch($val->name)
			{
			case "RunID":
                	    $runid=$rows[$val->name];
                	    $runtablebody.=str_replace("{runid}",$runid,$runtablerow);
			    break;
                	case "TestID":
                	    $testid=$rows[$val->name];
			    break;
                	case "RunName":
			    $runtestlink="suite.php?runid=$runid#testrow_$testid";
                	    $runtablebody.="<td value=\"".$rows[$val->name]."\"><a href=\"$runtestlink\" target=”_blank”>".$rows[$val->name]."</a></td>";
			    break;
                	default:
                	    $runtablebody.="<td value=\"".$rows[$val->name]."\">".$rows[$val->name]."</td>";
			}
			switch($val->name)
			{
			case "TestStartDate":
			    $teststartdate=$rows[$val->name];
			    break;
			case "TestResult":
			    $testresult=$rows[$val->name];
			    break;
			}
            	    }
            	    $tcol++;
            	}
		//$startdatesdata[$teststartdate]=1;
		$lblunique=$teststartdate."_".$testid;
		array_push($startdatesdata,array($lblunique, 1,'dlpos' => 'center', 'dlt' => 'bubble', 'dlp' => 3, 'dlr' => 3,
		    'dlf' => 'Times New Roman', 'dlfa' => 0.45,
		        'lbl' => $lblunique, 'dlbg' => array('#99f','#fff','#99f','h')));
		$linksdata[$lblunique]=$runtestlink;
		switch($testresult)
		{
		case "PASS":
		    array_push($colorsdata,array('green','#a3de9c'));
		    break;
		case "FAIL":
		    array_push($colorsdata,array('#A40808','red'));
		    break;
		case "ERROR":
		    array_push($colorsdata,array('#f55c3d','yellow'));
		    break;
		case "SKIP":
		    array_push($colorsdata,array('grey','lightgrey'));
		    break;
		default:
		    array_push($colorsdata,array('blue','white'));
		}
            	if ($currentfirstcolname==$runtablefirstcolname) {
                	$runtablebody.="</tr>";
            	}
            	$trid++;
            	}
            }
	    $result->free();
        }
        
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    $envoptions=array_unique($envoptions);
    foreach ($envoptions as $option) {
	$selected="";
	if($option==$environment)
	{
		$selected=" selected=\"selected\" ";
	}
	$envselection.="<option value=\"".$option."\" $selected>".$option."</option>";
    }
    $envselection.="</select>";
    
    $startdatesdata=array_reverse($startdatesdata);
    $colorsdata=array_reverse($colorsdata);
    $linksdata=array_reverse($linksdata);
//    echo var_dump($startdatesdata);
//    echo var_dump($colorsdata);
//    echo var_dump($linksdata);
    $runtableheader.="</tr></thead>";
    $runtable.=$runtableheader.$runtablebody;
    $runtable.="</tbody></table>";
    
    require_once 'SVGGraph/autoloader.php';

$settings = array(

  'auto_fit' => true,
  //'graph_title' => 'title',
  'back_colour' => 'lavender',
  'stroke_colour' => '#000',
  'back_stroke_width' => 0,
  'back_stroke_colour' => '#eee',
  'axis_colour' => '#000',
  'axis_overlap' => 2,
  'axis_font' => 'Arial',
  'axis_font_size' => 14,
  'grid_colour' => '#666',
  'show_tooltips' => false,
  'show_data_labels' => true,
  'data_label_angle' => -90,
  'data_label_fade_in_speed' => 40,
  'data_label_fade_out_speed' => 5,
  'label_colour' => '#000',
 // 'label_h' => 'test',
  'pad_right' => 20,
  'pad_left' => 20,
  'link_base' => '/',
  'link_target' => '_top',
  'minimum_grid_spacing' => 20,
 // 'datetime_keys' => true,
  'show_axis_text_v' => false,
  'show_axis_text_h' => false,
  'axis_text_angle_h' => -90,
  'structure' => array(
        'key' => 0, 'value' => 1,
        'data_label_type' => 'dlt',
        'data_label_padding' => 'dlp',
        'data_label_round' => 'dlr',
        'data_label_position' => 'dlpos',
        'data_label_fill' => 'dlbg',
        'data_label_font' => 'dlf',
        'data_label_font_adjust' => 'dlfa',
        'label' => 'lbl',
        )
);

//$values = array(
 //array('2019-10-16 10:29:33' => 1, '2019-10-16 11:05:44' => 1, '2019-10-16 14:00:35' => 1, '2019-10-16 14:20:25' => 1, '2019-10-16 14:33:13' => 1, '2019-10-16 15:34:25' => 1),
//    $startdatesdata
//);
$values =$startdatesdata;

//$colours = array(
//  array('red','yellow'), array('blue','white'), array('blue','white'), array('blue','white'), array('blue','white'), array('blue','white'),
    //&$colorsdata
//);
$colours=$colorsdata;
//$links = array(
// '2019-10-25 12:26:17' => 'crcdropper.php', '2019-10-28 10:29:09' => 'svggraph.php'
//);
$links=$linksdata;

$graph = new Goat1000\SVGGraph\SVGGraph(1200, 150, $settings);

$graph->colours($colours);
$graph->values($values);
$graph->links($links);
//$graph->render('BarGraph');

echo("<div>");
echo $envselection;
    echo $graph->fetch('BarGraph',false);
//$graph->render('ExplodedPie3DGraph');
echo("</div><div>");
//echo($dateselector);
echo("</div>");
echo $graph->fetchJavascript();
//    echo("requested test details $testid<br>");
//    echo("Page under construction");
    
    echo $runtable;
    
echo("<script  type=\"text/javascript\">

var envselection = document.getElementById('envselection');

//window.onload 
envselection.onchange = function(){
    console.log(envselection.value);
    console.log('".(($startdate_param!="NULL")?$startdate_param:"'NULL'")."');
    console.log('".(($starttime!="NULL")?$starttime:"'NULL'")."');
    console.log(\"".(($enddate_param!="NULL")?$enddate_param:"'NULL'")."\");
    console.log(\"".(($endtime!="NULL")?$endtime:"'NULL'")."\");
    reloadwindow(envselection.value,\"".(($startdate_param!="NULL")?$startdate_param:"NULL")."\",\"".(($starttime!="NULL")?$starttime:"NULL")."\",\"".(($enddate_param!="NULL")?$enddate_param:"NULL")."\",\"".(($endtime!="NULL")?$endtime:"NULL")."\");
}
function reloadwindow(environment,startdateValue,starttimeValue,enddateValue,endtimeValue) {
    window.open(\"testhistory.php?runname=$runnameparam&testname=$testnameparam\"
    +\"&startdate=\"+ startdateValue
    +\"&starttime=\"+ starttimeValue
    +\"&enddate=\"+ enddateValue
    +\"&endtime=\"+ endtimeValue
    +\"&environment=\"+ environment
    +\"&_=\" + new Date().getTime(),'_self',false);
}
</script>");
    echo("</body>
</html>");
            
CloseCon($mysqli);

?>
