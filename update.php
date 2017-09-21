<?php
//Include required specific tool functions
require_once("functions.php");

//Include required functions for pdf manipulation
require_once("pdftk-toolkit/vendor/autoload.php");

//include the LTI Library
require_once("LTI/functions/easyLTI.php");

//Get ready for sessions
session_start();

//Include functions
require_once("functions.php");

//Grab our user info via LTI or built session, die if no LTI or session
$ltiObject = ltiSessionCheck();

//Create a new assignment from the Instuctor Page
if ($_POST['formAction'] == "createAssignment"){

    //Make sure none of the fields are empty
    if(empty($_POST['assignmentName']) || empty($_POST['taskAStart']) || empty($_POST['taskAEnd']) || empty($_POST['url1']) || empty($_POST['pages'])){
        echo "Missing required items. Reload and try again.";
		die;
    }

    //Create a DB Handle
    $dbHandle = dbConnection();

    //Reset variables
    $urls="";
    unset($urlArray);

    //Mash the urls together for storage to DB ({-} is the delimiter) and into an Array for passing to pdfSplit
    for($i =0; $i<50; $i++){
        if(isset($_POST['url'.$i.''])) {
            $urlArray[]=$_POST['url'.$i.''];
        }
    }

    //Split the PDF's into individual pages and get back a list of urls done successfully to put in the db ({-} is the delimiter)
    $successUrls = pdfSplit($urlArray, $ltiObject->info['context_id']);
	
    //Grab all the assignments associated with the course
    $statement = 'INSERT INTO `assignmentInfo`(`cid`, `taskAOpen`, `taskAClose`, `numPages`, `name`) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE `taskAOpen` = ?, `taskAClose` = ?, `numPages` = ?, `name` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_SESSION['cid'], strtotime($_POST['taskAStart']), strtotime($_POST['taskAEnd']), $_POST['pages'], $_POST['assignmentName'], strtotime($_POST['taskAStart']), strtotime($_POST['taskAEnd']), $_POST['pages'], $_POST['assignmentName']));

    //Get the aid for the newly inserted assignment
    $lastId = $dbHandle->lastInsertId();

    //Push each of the assignment documents to the DB
    foreach ($successUrls as $url){
        $statement = 'INSERT INTO `assignmentDocuments`(`aid`, `url`, `filename`, `length`) VALUES (?,?,?,?)';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($lastId, $url['url'], $url['fileName'], $url['length']));
		
		$lastDid = $dbHandle->lastInsertId();
		
		//Create a documetPages entry for each page of the document, task A
		for($i = 1; $i<=$url['length']; $i++){
			$statement = 'INSERT INTO `documentPages`(`did`, `page`, `task`) VALUES (?,?,?)';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array($lastDid, $i, "A"));
		}	
		//Create a documetPages entry for each page of the document, task B
		for($i = 1; $i<=$url['length']; $i++){
			$statement = 'INSERT INTO `documentPages`(`did`, `page`, `task`) VALUES (?,?,?)';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array($lastDid, $i, "B"));
		}	
		//Create a documetPages entry for each page of the document, task C
		for($i = 1; $i<=$url['length']; $i++){
			$statement = 'INSERT INTO `documentPages`(`did`, `page`, `task`) VALUES (?,?,?)';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array($lastDid, $i, "C"));
		}			
    }

    //Push the user back to the header
    header("Location: instructor.php");
}

//Delete an assignment from the Instructor Page

//Submit & Update a student transcript
if (isset($_POST['transcriptSave'])){

    //Create a DB Handle
    $dbHandle = dbConnection();

    //Push the textarea data to save
    $statement = 'INSERT INTO `studentTranscripts` (`sid`, `task`, `filename`, `page`, `text`) VALUES (?,?,?,?,?)';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_POST['sid'], $_POST['task'], $_POST['filename'], $_POST['page'], $_POST['transcriptText']));

    //Push the student back to the student landing page and send their status
    header("Location: student.php?status=saved".$_POST['task']."");
}

//Final submit for grading for transcript
if (isset($_POST['transcriptFinal'])){

    //Create a DB Handle
    $dbHandle = dbConnection();

    //Push the textarea data to save
    $statement = 'INSERT INTO `studentTranscripts` (`sid`, `task`, `filename`, `page`, `text`) VALUES (?,?,?,?,?)';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_POST['sid'], $_POST['task'], $_POST['filename'], $_POST['page'], $_POST['transcriptText']));

    //Mark the transcript as finished
    $statement = 'UPDATE `assignedPages` SET `completed`= ?, `completedDate`=? WHERE `aid` = ? AND `sid` = ? AND task = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array("TRUE", time(), $_POST['aid'], $_POST['sid'], $_POST['task']));

    //Push the student back to the student landing page and send their status
    header("Location: student.php?status=submitted".$_POST['task']."");

}

//Student cancelled save so send them back to the dashboard
if ($_POST['transcriptCancel'] == "Back"){

    //Push the student back to the student landing page and send their status
    header("Location: student.php");
}

//Instructor cancelled save so send them back to the dashboard
if ($_POST['transcriptCancel'] == "Back to Overview"){

    //Push the student back to the student landing page and send their status
    header("Location: instructor.php");
}

//Save Instructor marking
if ($_POST['transcriptCancel'] == "Save Grading"){

    //Create a DB Handle
    $dbHandle = dbConnection();

    //Push the textarea data to save
    $statement = 'INSERT  INTO `grading` (`sid`, `task`, `grade`, `comment`) VALUES (?,?,?,?)';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_POST['sid'], $_POST['task'], $_POST['gradeNumber'], $_POST['gradeComments']));

    //Push the student back to the student landing page and send their status
    header("Location: instructor.php");
}


//Autosave from the student Transcript page
if (isset($_POST['text']) && $_POST['action'] == "autosave"){

    //Create a DB Handle
    $dbHandle = dbConnection();

    //Push the textarea data to save
    $statement = 'INSERT INTO `studentTranscripts` (`sid`, `task`, `filename`, `page`, `text`) VALUES (?,?,?,?,?)';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_POST['sid'], $_POST['task'], $_POST['filename'], $_POST['page'], $_POST['text']));
}

//Preview / Next save action: "textArea"
if (isset($_POST['text']) && $_POST['action'] == "textArea"){

    //Create a DB Handle
    $dbHandle = dbConnection();

    //Push the textarea data to save
    $statement = 'INSERT INTO `studentTranscripts` (`sid`, `task`, `filename`, `page`, `text`) VALUES (?,?,?,?,?)';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_POST['sid'], $_POST['task'], $_POST['filename'], $_POST['currentpage'], $_POST['text']));
}


//Ajax textarea load for the Transcript Page ckeditor content
if ($_POST['action'] == "textArea"){

    //Make a new dbhandle
    $dbHandle = dbConnection();

    //See if the student already has assigned pages
    $statement = 'SELECT `text` FROM `studentTranscripts` WHERE `sid` = ? AND `task` = ? AND `filename` = ? AND `page` = ? ORDER BY `tid` DESC';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_POST['sid'], $_POST['task'], $_POST['filename'], $_POST['page']));
    $pageResult = $exec->fetch(PDO::FETCH_ASSOC);

    //Send back the text pulled from the last time the student worked on this assignment sorted by most recent entry
    echo $pageResult['text'];
}

//Ajax textarea load for the Transcript Page Task C diff
if ($_POST['action'] == "diffC"){

   echo diffC($_POST['filename'], $_POST['page'], $_POST['startPage']);
}
