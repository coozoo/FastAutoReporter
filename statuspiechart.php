<?php
    require_once 'SVGGraph/autoloader.php';

    $PASS=0;
    $ERROR=0;
    $FAIL=0;
    $SKIP=0;
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $query_str = parse_url($url, PHP_URL_QUERY);
    $parts = parse_url($url);
    if(isset($parts['query']))
    {
	parse_str($parts['query'], $query);
    }    
    if(isset($query['PASS']) && is_numeric($query['PASS']))
    {
	$PASS=$query['PASS'];
    }
    if(isset($query['ERROR']) && is_numeric($query['ERROR']))
    {
	$ERROR=$query['ERROR'];
    }
    if(isset($query['FAIL']) && is_numeric($query['FAIL']))
    {
	$FAIL=$query['FAIL'];
    }
    if(isset($query['SKIP']) && is_numeric($query['SKIP']))
    {
	$SKIP=$query['SKIP'];
    }

$settings = array(
  'back_colour' => 'lavender',
  'stroke_colour' => '#000',
  'back_stroke_width' => 0,
  'back_stroke_colour' => '#eee',
  'pad_right' => 20,
  'pad_left' => 40,
  'pad_top' => -200,
  'link_base' => '/',
  'link_target' => '_top',
  'show_labels' => true,
  'show_label_percent' => true,
//  'data_label_type' => 'dlt',
  'show_label_key' => false,
//  'show_label_amount' => true,
  'label_font' => 'Arial',
  'label_font_size' => '12',
  'label_colour' => '#DED',
  'label_back_colour' => '#333',
  'label_position' => 0.70,
  'show_legend' => true,
//  'legend_position' => 'outer left 0 0',
//  'label_fade_in_speed' => '30',
//  'label_fade_out_speed' => '15',
    'aspect_ratio' => 0.6,
  'depth' => '10',
  'keep_colour_order' => true,
  //'start_angle' => '120',
  //'end_angle' => '60',
//  'units_before_label' => '$',
);

$values = array(
  'PASS' => $PASS, 'FAIL' => $FAIL, 'ERROR' => $ERROR,
  'SKIP' => $SKIP
);
/*$values = array(
  $PASS, $FAIL, $ERROR,$SKIP
);*/

$colors = array('#98fab2', '#A40808', '#f55c3d', '#dedede');
/*$links = array(
  'PASS' => 'jpegsaver.php', 'FAIL' => 'crcdropper.php',
  'ERROR' => 'svggraph.php', 'SKIP' => 'skip.php'
);*/

$settings['legend_entries'] = array(
  'PASS', 'FAIL', 'ERROR',
  'SKIP'
);
$graph = new Goat1000\SVGGraph\SVGGraph(300, 400, $settings);

    $cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);

echo("<!DOCTYPE html>
 <html>
  <head>
    <title>PieChart</title><style id=\"csstablestyle\">".$cssTableStyle."</style>
     </head>
      <body>
          <div>");

$graph->colours($colors);
$graph->values($values);
echo $graph->fetch('ExplodedPie3DGraph',false);
//$graph->render('ExplodedPie3DGraph');
echo("</div>");
echo $graph->fetchJavascript();
echo("</body>
 </html>");
?>
