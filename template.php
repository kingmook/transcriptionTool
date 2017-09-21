<?php
//Default Page
//Created by: mbrousseau(kingmook) & ctoscher

error_reporting(E_ALL);
ini_set('display_errors', 1);	

//Include required specific tool functions
require_once("functions.php");

//Grab our user info via LTI or built session, die if no LTI or session
$ltiObject = ltiSessionCheck();

//Include required functions for pdf manipulation
require_once("pdftk-toolkit/vendor/autoload.php");

//Default head builder
defaultHead("Dashboard");

//Minimum role allowed to view page (student, ta, or instructor)
authLevel("student", $ltiObject->info['ext_sakai_role']);

