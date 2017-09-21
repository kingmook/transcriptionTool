<?php
//Landing Page for LTI Transcription Tool
//Created by: mbrousseau(kingmook) & ctoscher

error_reporting(E_ALL);
ini_set('display_errors', 1);	

//Include required specific tool functions
require_once("functions.php");

//Include required functions for pdf manipulation
require_once("pdftk-toolkit/vendor/autoload.php");

//include the LTI Library
require_once("LTI/functions/easyLTI.php");	

//Get ready for sessions
session_start();

//Grab our user info via LTI or built session, die if no LTI or session
$ltiObject = ltiSessionCheck(TRUE);

//Push info about the course into the db if needed
$_SESSION['cid'] = checkCourse($ltiObject);

//Minimum role allowed to view page (student, ta, or instructor)
authLevel("student", $ltiObject->info['ext_sakai_role'], "forward", $ltiObject);
