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
    $parts = parse_url($url);
    $caseid="NULL";
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (isset($query['caseid'])) {
        $caseid=$query['caseid'];
    }
    else
    {
	echo "required caseid";
	exit;
    }
    preg_match_all('!\d+!', $caseid, $matches);
    $caseid=$matches[0][0];
    require 'stuff/testrail.php';

    $client = new TestRailAPIClient("$testrailhost");
    $client->set_user("$testrailuser");
    $client->set_password("$testrailpass");
    $case = $client->send_get("get_case/$caseid");
    $suite = $client->send_get("get_suite/".$case['suite_id']);

    $stepstable="<table id=\"stepstable\" class=\"blueTable\">";
    $stepstableheader="<thead><tr><th>Steps</th><th>Expected Result</th></tr></thead>";
    $stepstablebody="<tbody>";
    $stepstablerows=0;
    if(!is_null($case['custom_steps_to_reproduce']))
    {
    foreach($case['custom_steps_to_reproduce'] as $step)
    {
	$stepstablebody.="<tr><td style=\"min-width:30em;\"><pre>".$step['content']."</pre></td>";
	$stepexpected=$step['expected'];
	$intablemark=false;
	$stepexpectedres="";
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $stepexpected) as $line){
	    if(strpos($line, '|||') !== false)
	    {
		$intablemark=true;
		//ignore alignment
		$line=str_replace("|:","|",$line);
		$line=str_replace(":|","|",$line);
		$line=str_replace("|||","<table  class=\"minimalistBlack\" style=\"white-space: -o-pre-wrap;word-wrap: break-word;white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;\"><thead><tr><th>",$line);
		$line=str_replace("|","</th><th>",$line);
		$line.="</th></tr></thead><tbody>";
	    }
	    elseif((strpos($line, '||') !== false && $intablemark==true))
	    {
		$line=str_replace("||","<tr><td style=\"white-space: -o-pre-wrap;word-wrap: break-word;white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;\">",$line);
		$line=str_replace("|","</td><td style=\"white-space: -o-pre-wrap;word-wrap: break-word;white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;\">",$line);
		$line.="</td></tr>";
	    }
	    elseif((strpos($line, '||') === false && $intablemark==true))
	    {
		$line.="</tbody></table>";
		$intablemark=false;
	    }
	    else
	    {
		$line.="\n";
		$intablemark=false;
	    }
	    $stepexpectedres.=$line;
	}
	//var_dump($stepexpectedres);
	$stepstablebody.="<td><pre>".$stepexpectedres."</pre></td></tr>";
	if($step['content']!=="" || $step['expected']!=="")
	{
	    $stepstablerows++;
	}
    }
    }
    $stepstablebody.="</tbody>";
    $stepstable.=$stepstableheader.$stepstablebody."</tbody>";
    
        $cssTableStyleFile="csstablestyle.css";
    $cssTableStyle=file_get_contents($cssTableStyleFile);
    // The Regular Expression filter
    $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?(:[0-9]{1,5})?/";
    $refs="";
    $refs=$case['refs'];
    $preconds=$case['custom_preconds'];
    $precondsresult = preg_replace_callback($reg_exUrl, function($match) {
    $url = $match[0];
    return sprintf('<a href="%s$1" target=\"_blank\">%1$s</a>', $url);
    }, $preconds);
    echo("<!DOCTYPE html><html>
    <head><title>Fast Automation Report Viewer</title>
        <link rel='shortcut icon' type='image/png' href='$iconfile' /> <style id=\"csstablestyle\">".$cssTableStyle."</style>
      <style>
         pre {
            overflow-x: auto;
            white-space: pre-wrap;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;
            white-space: -o-pre-wrap;
            word-wrap: break-word;
         }
      </style>
        </head>
        <body><div><table>");

    //echo("<font style=\"font-weight:bold;\">".$case['title']."</font>");
    echo("<tr><td><font style=\"font-weight:bold;\">Suite:</font></td><td style=\"text-align: left;\"><a href=\"".str_replace("http:","https:",$suite['url'])."\" target=\"_blank\">".$suite['name']."</a></td></tr>");
    // onclick=\"window.open('', '_self', ''); window.close();\"
    echo("<tr><td><font style=\"font-weight:bold;\">Testcase:</font></td><td style=\"text-align: left;\"><a href=\"$testrailhost"."index.php?/cases/view/".$case['id']."\" target=\"_blank\">".$case['title']."</a></td></tr>");
    if($refs!="")
    {
	echo("<tr style=\"vertical-align:top;\"><td><font style=\"font-weight:bold;\">References:</font></td><td>".$refs."</td></tr>");
    }
    if($precondsresult!="")
    {
	echo("<tr style=\"vertical-align:top;\"><td><font style=\"font-weight:bold;\">Preconditions:</font></td><td><pre>".$precondsresult."</pre></td></tr>");
    }
    if($stepstablerows>0)
    {
        echo "<tr><td colspan=2>".$stepstable."</td></tr>";
    }
    echo("</table></div>");
//    echo("<script  type=\"text/javascript\">
//    window.focus();
//    </script>");

    echo("</body></html>");
?>
