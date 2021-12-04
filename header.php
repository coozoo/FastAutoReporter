<?php
    $heightimage="75px";
    $titleimage="";
    $logoimage="/$myreporter/img/normal/output.png";
    $runlogos=array(
	array(
	    "logoimage" => "/$myreporter/img/anim/progress1.gif",
	    "titleimage" => "Caramba"
	    ),
	array(
	    "logoimage" => "/$myreporter/img/anim/progress2.gif",
	    "titleimage" => "Caramba"
	    )
    );

    if(isset($STATUS))
    {
    switch ($STATUS)
    {
    case "InProgress":
	$rand_index=array_rand($runlogos);
	$logoimage=$runlogos[$rand_index]["logoimage"];
	$titleimage=$runlogos[$rand_index]["titleimage"];
	$heightimage="80px";
	break;
    case "FAIL":
	$logoimage="/$myreporter/img/normal/fail.png";
	$titleimage="Eat my shorts";
	$heightimage="80px";
	break;
    case "ERROR":
	$logoimage="/$myreporter/img/normal/error.png";
	$titleimage="How dare you";
	$heightimage="80px";
	break;
    case "SKIP":
	$logoimage="/$myreporter/img/normal/skip.png";
	$titleimage="Hm.....";
	$heightimage="80px";
	break;
    case "PASS":
	$logoimage="/$myreporter/img/normal/pass.png";
	$titleimage="Really?";
	$heightimage="80px";
	break;
    case "blame":
	$logoimage="/$myreporter/img/normal/blame.png";
	$titleimage="Blame it on you!";
	$heightimage="80px";
	break;
    default:
	$logoimage="/$myreporter/img/normal/output.png";
	$titleimage="Be cool";
	$heightimage="70px";
    }
    }
    else
    {
	$logoimage="/$myreporter/img/normal/output.png";
	$titleimage="Be cool";
	$heightimage="70px";
    }
    if(isset($headertest))
    {
	$headertest="<a  class=\"active\" style=\"color:black;font-weight:bold;cursor:pointer;position: relative;z-index: 5;\" onclick=\"return headertest();\" title=\"Build Stat For Filtered Items\">Build Stat</a>";
    }
    else
    {
	$headertest="";
    }
echo("<div class=\"header\">
  <a href=\"index.php\" class=\"logo\"><img src=\"$logoimage\" style=\"height:$heightimage\" title=\"$titleimage\" alt=\"home\"></a>
  <div class=\"logotext\">Fast Automation Reporter
  </div>
  <div class=\"header-right\">
    <!--<a class=\"active\" href=\"#home\">Home</a>
    <a style=\"position: relative;z-index: 5;\" href=\"systeminfo.php\">System Info</a>
    <a  style=\"position: relative;z-index: 5;\" href=\"#about\">About</a>-->
    <!--<a href=\"systeminfo.php\"><img src=\"img/icons/Gnome-utilities-system-monitor.svg\" style=\"height:5;\" title=\"System Info\" alt=\"System Info\"></a>-->
    <a style=\"position: relative;z-index: 5;\" href=\"systeminfo.php\" title=\"System Info\">System Info</a>
    ".$headertest."
  </div>
</div>");
?>
