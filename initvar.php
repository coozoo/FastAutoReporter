<?php
    $iconfile="img/normal/icontiny.png";

// xxxxxEnabled flags will show hide any appearance in UI like columns or buttons

///////////// testrail settings /////////////
//enable testrail columns
$testrailEnabled=true;
//user and password/token
$testrailhost='https://testrail.com/';
$testrailuser='TESTRAILUSER';
$testrailpass='TESTRAILPASS';

/////////////////////////////////////////////

///////////// JIRA settings /////////////////
//jira column currently simply text field
$jiraEnabled=false;
$jirahost='https://JIRAPROJECT.atlassian.net';
$jirauser='JIRAUSER';
$jiratoken='JIRATOKEN';
//// Xray cloud settings heavily depended on jira ///////////
//enable xray column
$xrayEnabled=true;
//eu server could be not localized
$xrayhost='https://eu.xray.cloud.getxray.app';
//project key can be found in project settigns
$projectkey='PROJECTKEY';
//it is path to xray plugin in jira
$xraypluginpath = '/plugins/servlet/ac/com.xpandit.plugins.xray/testing-board';
//these keys for non working xray api they're not used
//$xrayuser='XRAYUSER';
//$xraykey='XRAYKEY';
//$xraytoken='XRAYTOKEN';
/////////////////////////////////////////////
?>
