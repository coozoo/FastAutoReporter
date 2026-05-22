<?php
    $iconfile="img/normal/icontiny.png";

// xxxxxEnabled flags will show hide any appearance in UI like columns or buttons

///////////// testrail settings /////////////
//enable testrail columns
$testrailEnabled=false;
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
$projectkey='JIRAPROJECT_XRAYCONNECTED';
$xrayclientid='XRAYCLIENTID';
$xrayclientsecret='XRAYCLIENTSECRET';
/////////////////////////////////////////////
// --- COPILOT AI CONFIGURATION ---
$copilotEnabled=true;

//this will be used to search in project
$copilot_pat="GIT_PAT_WITHPROJECTACCESS";

$copilot_free_max_total_chars = 40000;
$copilot_free_max_line_chars = 500;
$copilot_free_model = 'gpt-4o';

$copilot_paid_max_total_chars = 120000;
$copilot_paid_max_line_chars = 1500;
$copilot_paid_model = 'gemini-2.5-pro';

$ai_git_projects = array(
    "Gateway (Backend)" => array(
        "repo" => "GIT_PROJECT",
        "pat"  => "GIT_PAT_WITHPROJECTACCESS",
		"prompt" => "You are a Senior QA Automation Engineer analyzing a failed test in the \"blabla\" repository.\n" .
                    "1. Deduce the exact Java file path (e.g., src/test/java/...) from the package name in the stack trace or test name or id of test case and explicitly print it at the top of your analysis.\n"                 
    ),
    "Web (Frontend)" => array(
        "repo" => "ANOTHER_GIT_PROJECT",
        "pat"  => "GIT_PAT_WITHPROJECTACCESS",
		"prompt" => "test front copilot_system_prompt"
    )
);

// The Master Prompt
$copilot_system_prompt = 'You are a Senior QA Automation Engineer analyzing a failed test in the repository.
1. Deduce the exact Java file path (e.g., src/test/java/...) from the package name in the stack trace or test name or id of test case and explicitly print it at the top of your analysis.
2. Analyze the provided Jira/Xray context to establish the expected business behavior and explain what story this test tells.
3. Read the provided logs and stack trace to identify the root cause, comparing the actual failure against the expected Jira/Xray story.
4. If the logs show an infrastructure, environment, or test data issue (e.g., missing userpool player, captcha blocked, DB error), explain the setup/data issue clearly based on the stack trace. DO NOT write fake code snippets for data/infra issues.
5. If the failure is an actual API assertion failure or code logic bug, provide the exact Java code snippet to fix it in the test framework.';

?>
