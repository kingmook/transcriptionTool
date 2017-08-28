<?php
/* Example Hello World (Hello Object?) Provider using easyLTI */
require_once("functions/easyLTI.php");

//Call connectLTI to make the secure LTI connection based on the info in config.php
$ltiObject = connectLTI();

//How we access the object's info array - Pull site name as example
echo '<h1>' . $ltiObject->info['context_title'] . '</h1>';

//Print out the whole object and make it look nice
echo "<pre>";
print_r($ltiObject);
echo "</pre>";


