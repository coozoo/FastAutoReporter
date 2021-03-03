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

    $postdata = $_POST["data"];
//    var_dump($postdata);
    $runs = json_decode($postdata, true);
//    var_dump($runs);
    $structureddata=array();
    $valuesdata=array();
    $passdata=array();
    $faildata=array();
    $errordata=array();
    $skipdata=array();
    $colorsdata=array();
    $linksdata=array();
    $envoptions=array();

    $durationdata=array();
/*
  'Name' => string '<b><a href="suite.php?runid=1870" target="_blank">Night_Regression_114055</a></b>' (length=81)
  'Status' => string 'Finished' (length=8)
  'Version' => string '1.0.745' (length=7)
  'Start at' => string '2019-11-08 01:41:21' (length=19)
  'Finished at' => string '2019-11-08 03:59:27' (length=19)
  'Duration' => string '02:18:06' (length=8)
  'Environment' => string 'TEST' (length=4)
  'Team' => string 'VB_BE' (length=5)
  'FAIL' => string '21' (length=2)
  'ERROR' => string '6' (length=1)
  'SKIP' => string '20' (length=2)
  'PASS' => string '604' (length=3)
  'Total' => string '651' (length=3)
*/
    foreach(array_reverse($runs['data']) as $run) 
    {
	//var_dump($run);
$str_time = $run['Duration'];
$str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $str_time);
sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
$time_seconds = $hours * 3600 + $minutes * 60 + $seconds;
	
	$label=$run['Start at'];
	array_push($structureddata,array($run['Start at'],'TOTAL' => $run['Total'],'ERROR' => $run['ERROR'],'FAIL' => $run['FAIL'],'SKIP' => $run['SKIP'],'PASS' => $run['PASS'],'dlpos' => 'bottom', 'dlt' => 'bubble', 'dlp' => 3, 'dlr' => 3,
		    'dlf' => 'Times New Roman', 'dlfa' => 0.45,
		        'TOTALlbl' => $run['Total'],'ERRORlbl' => $run['ERROR'],'FAILlbl' => $run['FAIL'],'SKIPlbl' => $run['SKIP'],'PASSlbl' => $run['PASS'] , 'dlbg' => array('#99f','#fff','#99f','h')));
	array_push($durationdata,array($run['Start at'],'Duration' => $time_seconds,'dlpos' => 'bottom', 'dlt' => 'square', 'dlp' => 3, 'dlr' => 3,
		    'dlf' => 'Times New Roman', 'dlfa' => 0.45,
		        'lbl' => $run['Duration'] , 'dlbg' => array('#99f','#fff','#99f','h')));
//	$errordata[$label] = $run['ERROR'];
//	$faildata[$label] = $run['FAIL'];
//	$skipdata[$label] = $run['SKIP'];
//	$passdata[$label] = $run['PASS'];
	preg_match('/href="(.*?)"/', $run['Name'], $matches);
		$linksdata[$run['Start at']]=$matches[1];
	/*	switch($testresult)
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
		}*/
    }
//    array_push($valuesdata,$errordata);
//    array_push($valuesdata,$faildata);
//    array_push($valuesdata,$skipdata);
//    array_push($valuesdata,$passdata);
//    $valuesdata=array_reverse($valuesdata);
//    $colorsdata=array_reverse($colorsdata);
//    $linksdata=array_reverse($linksdata);

$cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
            echo("<!DOCTYPE html><html lang=\"en\"><head><title>Test History</title><link rel=\"icon\" type=\"image/png\" href=\"$iconfile\"/><style id=\"csstablestyle\">".$cssTableStyle."</style>
        	    </head><body>");

require_once 'SVGGraph/autoloader.php';


$settings = array(
  'auto_fit' => true,
  'graph_title' => 'Results by Run',
  'back_colour' => 'lavender',
  'stroke_colour' => '#000',
  'back_stroke_width' => 0,
  'back_stroke_colour' => '#eee',
  'axis_colour' => '#000',
  'axis_overlap' => 2,
  'axis_font' => 'Arial',
  'axis_font_size' => 10,
  'grid_colour' => '#666',
  'show_legend' => true,
  'legend_position' => 'outer left 0 200',
  'legend_entries' => array('ERROR','TOTAL','FAIL','SKIP','PASS'),
  'show_tooltips' => false,
  'show_data_labels' => true,
  //'data_label_angle' => -90,
  'data_label_fade_in_speed' => 40,
  'data_label_fade_out_speed' => 5,
  'label_colour' => '#000',
//  'label_v' => array('Amount', 'Time'),
 // 'label_h' => 'test',
  'pad_right' => 20,
  'pad_left' => 20,
  'link_base' => '/',
  'link_target' => '_top',
  'minimum_grid_spacing' => 20,
  //'datetime_keys' => true,
  //'show_axis_text_v' => false,
  //'show_axis_text_h' => false,
  'axis_text_angle_h' => -45,
//  'dataset_axis' => array(0),
//  'line_dataset' => array(3, 3),
  'structured_data' => true,
    'structure' => array(
        'key' => 0, 
	'value' => array('ERROR','TOTAL','FAIL','SKIP','PASS'),
        'data_label_type' => 'dlt',
        'data_label_padding' => 'dlp',
        'data_label_round' => 'dlr',
        'data_label_position' => 'dlpos',
        'data_label_fill' => 'dlbg',
        'data_label_font' => 'dlf',
        'data_label_font_adjust' => 'dlfa',
        'label' => array('ERRORlbl','TOTALlbl','FAILlbl','SKIPlbl','PASSlbl'),
        )
);
/*
//$values = array(
// array('2019-10-16 10:29:33' => 1, '2019-10-16 11:05:44' => 1, '2019-10-16 14:00:35' => 1, '2019-10-16 14:20:25' => 1, '2019-10-16 14:33:13' => 1, '2019-10-16 15:34:25' => 1),
//    $startdatesdata
//);
$values =$valuesdata;

//$colours = array(
//  array('red','yellow'), array('blue','white'), array('blue','white'), array('blue','white'), array('blue','white'), array('blue','white'),
    //&$colorsdata
//);
$colours=$colorsdata;
//$links = array(
// '2019-10-25 12:26:17' => 'crcdropper.php', '2019-10-28 10:29:09' => 'svggraph.php'
//);
$links=$linksdata;

$graph = new Goat1000\SVGGraph\SVGGraph(1200, 500, $settings);

print_r($values);
print_r($links);
//$graph->colours($colours);
$graph->values($values);
$graph->links($links);
*/
//$values = array(
// array('Dough' => 30, 'Ray' => 50, 'Me' => 40, 'So' => 25, 'Far' => 45, 'Lard' => 35),
// array('Dough' => 20, 'Ray' => 30, 'Me' => 20, 'So' => 15, 'Far' => 25, 'Lard' => 35,
//  'Tea' => 45)
//);

//$values =$valuesdata;
$values=$structureddata;
//var_dump($values);
$colours = array(
    array('#f55c3d','yellow'),array('blue'),array('#A40808','red'),array('grey','lightgrey'),array('green','#a3de9c')
);
$links=$linksdata;
//$links = array(
//  'Dough' => 'jpegsaver.php', 'Ray' => 'crcdropper.php',
//  'Me' => 'svggraph.php'
//);

$graph = new Goat1000\SVGGraph\SVGGraph(1200, 400, $settings);

$graph->colours($colours);
$graph->values($values);
$graph->links($links);


$settingsline = array(
  'auto_fit' => true,
  'graph_title' => 'Execution Time',
  'back_colour'       => 'lavender',
  'stroke_colour'     => '#000',
  'back_stroke_width' => 0,
  'back_stroke_colour'=> '#eee',
  'axis_colour'       => '#333',
  'axis_overlap'      => 2,
  'axis_font' => 'Arial',
  'axis_font_size'    => 10,
  'grid_colour'       => '#666',
  'label_colour'      => '#000',
  'pad_right'         => 20,
  'pad_left'          => 20,
  'link_base'         => '/',
  'link_target'       => '_top',
  'minimum_grid_spacing' => 20,
  'show_data_labels' => true,
  //'data_label_angle' => -90,
  'data_label_fade_in_speed' => 40,
  'data_label_fade_out_speed' => 5,
  //'show_axis_text_v' => false,
  'units_label'=> 's',
  //'show_axis_text_h' => false,
  'axis_text_angle_h' => -45,
  'fill_under'        => array(true),
  'marker_size'       => 5,
//  'marker_type'       => array('circle', 'square'),
//  'marker_colour'     => array('blue', 'red'),
    'structured_data' => true,
    'structure' => array(
        'key' => 0, 
	'value' => array('','Duration'),
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

//$valuesline = array(
// array('Dough' => 30, 'Ray' => 50, 'Me' => 40, 'So' => 25, 'Far' => 45, 'Lard' => 35),
// array('Dough' => 20, 'Ray' => 30, 'Me' => 20, 'So' => 15, 'Far' => 25, 'Lard' => 35,
//  'Tea' => 45)
//);

$coloursline = array(array('white'),
  array('#983208','red','orange', 'lightgreen'), array('blue', 'white')
);
/*$linksline = array(
  'Dough' => 'jpegsaver.php', 'Ray' => 'crcdropper.php',
  'Me' => 'svggraph.php'
);*/

$linksline=$linksdata;
 
$graphline = new Goat1000\SVGGraph\SVGGraph(1200, 200, $settingsline);
//var_dump($durationdata);
$graphline->colours($coloursline);
$graphline->values($durationdata);
$graphline->links($linksline);
//$graphline->render('LineGraph');
echo("<div style=\"height:100%;\">");
//echo $graphline->fetch('LineGraph',false);
echo $graphline->fetch('StackedBarAndLineGraph',false);
echo $graph->fetch('StackedBarAndLineGraph',false);
    echo("</div></body></html>");
    echo $graph->fetchJavascript();
    echo $graphline->fetchJavascript();
?>
