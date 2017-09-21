<?php
/* Producer library for LTI using the ims-blti library plus storage of nonces */
/* MBrousseau July 2015 */

//Bring in the IMS Basic LTI Functions
require_once("LTI/ims-blti/blti.php");

//Bring in the LTI and DB info
require_once("LTI/config.php");

//connect to LTI | Returns LTI Data or FALSE
function connectLTI()
{

    //Make the LTI connection | the Secret | Store as Session | Redirect after success
    $context = new BLTI($GLOBALS['ltiSecret'], true, false);

    //Valid LTI connection
    if ($context->valid == true) {

        //Check if nonce exists within 90 minute timeline
        if (secureLTI($_REQUEST['oauth_nonce'], $_REQUEST['oauth_timestamp'])) {
            ;
        }

    } //Invalid LTI connection
    else {
        echo "Unable to make a valid LTI connection. Refresh and try again.";
        die;
    }

    //LTI connection made successfully and nonce is OK. Return the LTI object
    return $context;
}

//Check and store timestamp and nonce | Returns TRUE or dies if replay nonce used
function secureLTI($nonce, $timestamp)
{

    //Connect to the DB
    $dbHandle = dbConnect();
    if ($dbHandle != false) {

        //Check to see if the Nonce already exists in the DB
        $stmt = $dbHandle->prepare("SELECT `timestamp` FROM `LTI` WHERE `nonce` = ?");
        $stmt->execute(array($nonce));

        //Nonce exists in DB - No replay for you
        if ($stmt->rowCount() != 0) {
            echo "Error Connecting to DB(2)";
            die;
        } //Insert the nonce and timestamp into the db
        else {
            $stmt = $dbHandle->prepare("INSERT INTO `LTI`(`nonce`, `timestamp`) VALUES (?, ?)");
            $stmt->execute(array($nonce, $timestamp));
        }
    } //Not able to connect to DB
    else {
        echo "Error Connecting to DB(1)";
        die;
    }

    //All's well with the nonce - Return TRUE
    return true;
}

//Connect to the db and return the db handle | Returns DB Handle or FALSE
function dbConnect()
{

    //PDO to the database
    $db = new PDO('mysql:dbname=' . $GLOBALS['dbName'] . ';host=' . $GLOBALS['dbHost'] . ';charset=utf8',
        $GLOBALS['dbUser'], $GLOBALS['dbPass']);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Return the DB Handle
    return $db;
}
?>