<?php

/*----------AUTHENTICATION FUNCTIONS----------*/
/*--------------------------------------------*/


//Check for a created session and if not create one | Returns the LTI object
function ltiSessionCheck($flush){

	//Check if the session is set
	if(isset($_SESSION['LTI']) && $flush != TRUE){
		//Return the unserialized object for use
		return unserialize($_SESSION['LTI']);
	}
	//If not
	else{
		
		//Make our LTI connection
		$ltiObject = connectLTI();
		
		//Make a serialized session from the LTI object
		$_SESSION['LTI'] = serialize($ltiObject);
		
		//Return the unserialized object for use
		return unserialize($_SESSION['LTI']);	
	}
}

//Users level of authentication based on their roles. Amalgamates sakai roles into simple terms | Returns instructor, ta or student
function authLevel($minRole, $userRole, $forward, $object){

    //Get ready for sessions
    session_start();

	//Convert the roles into auth levels
	//Always trimming
	$userRole = trim($userRole);

	//Put the user's info into the database if it doesn't already exist
    $dbHandle = dbConnection();

    //Check if the user is already there
    $statement = 'SELECT `sid` FROM `userInfo` WHERE `fullName` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($object->info['lis_person_name_full']));
    $sidResult = $exec->fetch(PDO::FETCH_ASSOC);

    //If the user isn't in the DB put them in there
    if(!$sidResult) {
        //Insert information about the class into the db
        $statement = 'INSERT INTO `userInfo`(`campusId`, `fullName`, `role`, `cid`) VALUES (?,?,?,?)';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($object->info['lis_person_sourcedid'], $object->info['lis_person_name_full'], $userRole, $_SESSION['cid']));
        $sidResult['sid'] = $dbHandle->lastInsertId();

    }

    //Add the user's sid to the session
    $_SESSION['sid'] = $sidResult['sid'];
    $_SESSION['role'] = $userRole;

	//Default level
	$userLevel = 0;
	
	//For the Instructor and Administrator roles authLevel 3
	if($userRole == "Instructor" || $userRole == "Administrator"){
		$userLevel = 3;
	}
	
	//For the TA, Organizer and Liasion Librarian roles authLevel 2
	if($userRole == "Teaching Assistant" || $userRole == "Organizer" || $userRole == "Liaison Librarian"){
		$userLevel = 2;
	}
	
	//For the Student and Audit roles authLevel 1
	if($userRole == "Student" || $userRole == "Audit"){
		$userLevel = 1;
	}
	
	//Convert the minLevel into auth levels
	//Always trimming
	$minRole = trim($minRole);
	
	//For the Instructor and Administrator roles authLevel 3
	if($minRole == "instructor"){
		$minLevel = 3;
	}
	
	//For the TA, Organizer and Liasion Librarian roles authLevel 2
	if($minRole == "ta"){
		$minLevel = 2;
	}
	
	//For the Student and Audit roles authLevel 1
	if($minRole == "student"){
		$minLevel = 1;
	}
	
	//Now check if their level is acceptable for access
	minAuthLevel($minLevel, $userLevel);	
	
	if ($forward == "forward"){
		
		switch ($userLevel){
			case 1:
				header("Location: student.php");
				die();
			break;
			
			case 2:
				header("Location: ta.php");
				die();
			break;
			
			case 3:
				header("Location: instructor.php");
				die();
			break;
		}
	}
}

//Minmum level of authentication required to view a page
function minAuthLevel($minLevel, $userLevel){

	//If ther users level of authentication is lower than required to view this page stop them.
	if ($userLevel < $minLevel){
		echo "You do not have access to view this page";
		die;
	}
	
}


/*----------DATABASE FUNCTIONS----------*/
/*--------------------------------------*/

//Connect to the db and return the db handle | Returns DB Handle or FALSE
function dbConnection()
{

    //PDO to the database set in config
    $db = new PDO('mysql:dbname=' . $GLOBALS['dbName'] . ';host=' . $GLOBALS['dbHost'] . ';charset=utf8', $GLOBALS['dbUser'], $GLOBALS['dbPass']);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Return the DB Handle
    return $db;
}


/*----------CONTENT FUNCTIONS----------*/
/*-------------------------------------*/

//Default <header> with script and css included | Prints the default header
function defaultHead($title){



	$header='
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>

		<title>'.$title.'</title>
		<meta name="description" content="" />
		<meta name="keywords" content="" />
		<meta name="robots" content="index,follow" />
		<script src="js/jquery-ui-1.11.4/external/jquery/jquery.js"></script>
		<script src="js/jquery-ui-1.11.4/jquery-ui.min.js"></script>
		<link rel="stylesheet" type="text/css" href="js/jquery-ui-1.11.4/jquery-ui.min.css" />
		<link rel="stylesheet" type="text/css" href="css/styles.css" />
		<link rel="stylesheet" type="text/css" href="css/legacy_one_file.css" />
		<!--Initalize Datatables-->
		<script src="//cdn.datatables.net/1.10.9/js/jquery.dataTables.min.js"></script>
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.9/css/jquery.dataTables.min.css" />
	</head>';

	echo $header;
    header('Access-Control-Allow-Origin: https://cpi.brocku.ca');
}

//Return status messages based on status inputs
function checkStatus($status){

    if($status == "savedA"){
        return "Saved Transcript for Task A";
    }
    if($status == "savedB"){
        return "Saved Transcript for Task B";
    }
    if($status == "submittedA"){
        return "Submitted Task A Transcript for Grading";
    }
    if($status == "submittedB"){
        return "Submitted Task B Transcript for Grading";
    }

    //Otherwise
    return null;
}


//See if the course exists in our db, if not add it. | Returns the CID of the current course
function checkCourse($object){

    //Put the user's info into the database if it doesn't already exist
    $dbHandle = dbConnection();

    $statement = 'SELECT `cid` FROM `classInfo` WHERE `className` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($object->info['context_id']));
    $cidResult = $exec->fetch(PDO::FETCH_ASSOC);

    //If the class isn't in the DB put it there
    if(!$cidResult) {
        //Insert information about the class into the db
        $statement = 'INSERT INTO `classInfo`(`className`, `lms`) VALUES (?,?)';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($object->info['context_id'], $object->info['ext_lms']));
        return $dbHandle->lastInsertId();
    }

    return $cidResult['cid'];

}


//Check if the student has pages assigned for a specific task and if not assign them, don't assign the same pages twice
function assignedPages($aid, $sid, $task){

    //Make a new dbhandle
    $dbHandle = dbConnection();

    //See if the student already has assigned pages
    $statement = 'SELECT * FROM `assignedPages` WHERE `aid` = ? AND `sid` = ? AND `task` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($aid, $sid, $task));
    $pageResult = $exec->fetch(PDO::FETCH_ASSOC);
	
    //If they don't already have assigned pages then let's give them some
    if(!$pageResult){

        //Figure out how many pages we're assigning
        $statement = 'SELECT `numPages` FROM `assignmentInfo` WHERE `aid` = ?';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($aid));
        $pages = $exec->fetch(PDO::FETCH_ASSOC);
        $pages = $pages['numPages'];
		$aPages = $pages;

        //Check the assignmentDocument table for documents that aren't fully assigned
        $statement = 'SELECT `did`, `filename`, `length` FROM `assignmentDocuments` WHERE `aid` = ? AND `fullyAssigned'.$task.'` = ? ORDER BY `did` ASC';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($aid, 0));

        //Grab the first result
        $docResult = $exec->fetch(PDO::FETCH_ASSOC);

        //If there are no pages left to assign (no fully unassigned documents)
        if(!$docResult){
            return FALSE;
        }
		
		$ofset = 0;
		$newPageResult['page'] = 0;
		
		while(TRUE == TRUE){
			//Find the next unassigned page
			$statement = 'SELECT * FROM `documentPages` WHERE `did` = ? AND `assigned` = ? AND `task` = ? AND `page` >= ? ORDER BY `oid` ASC';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array($docResult['did'], 0, $task, ($newPageResult['page']+$ofset)));
			$newPageResult = $exec->fetch(PDO::FETCH_ASSOC);
			
			//If our query comes back false we likely ran out of pages, move to next document
			if ($newPageResult == FALSE){
				$docResult['did']++;
				
				//We've changed documents so get the updated info
				$statement = 'SELECT * FROM `assignmentDocuments` WHERE `did` = ?';
				$exec = $dbHandle->prepare($statement);
				$exec->execute(array($docResult['did']));

				//Grab the first result
				$docResult = $exec->fetch(PDO::FETCH_ASSOC);
			}

			//Check if the student has been assigned the same page for the other tasks
			$statement = 'SELECT `pageStart`, `pageEnd` FROM `assignedPages` WHERE `sid` = ? AND `aid` = ? AND `filename` = ? AND `pageStart` = ?';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array($sid, $aid, $docResult['filename'], $newPageResult['page']));
			$pageStart = $exec->fetch(PDO::FETCH_ASSOC);
			
			//We're switching documents
			if($newPageResult == FALSE){
				
				$ofset = 0;
				$newPageResult['page'] = 0;	
				$pageStart = TRUE;
				$pages = $aPages;
			}
			//Student is already assigned this set of pages increase the page range to check
			elseif ($pageStart['pageStart'] == $newPageResult['page']){

				$pages = $pages+$aPages;	
				//$ofset = $ofset+$aPages;
				$ofset = $aPages;	
				$skipPage = TRUE;
			}
			
			//Range is not assigned to the student so move on
			else{

				break;
			}			
		}
		
		//Add the assigned pages to the student in the assignedPages table
        $statement = 'INSERT INTO `assignedPages`(`aid`, `sid`, `filename`, `pageStart`, `pageEnd`, `task`) VALUES (?,?,?,?,?,?)';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($aid, $sid, $docResult['filename'], $newPageResult['page'], ($newPageResult['page']+$aPages-1), $task));

		//Add the assigned pages to the documentPages table
		for($i = $newPageResult['page']; $i < ($newPageResult['page']+$aPages); $i++){
			$statement = 'UPDATE `documentPages` SET `assigned`=?,`sid`=? WHERE `did` = ? AND `page` = ? AND `task` = ?';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array(1, $_SESSION['sid'], $docResult['did'], $i, $task));
		}
		
        //By default the file shouldn't be fully assigned
        $fullyAssigned = 0;

        //Check if the file is now fully assigned (there are less pages left then are to be assigned to each student
        if (($docResult['length'] - ($newPageResult['page']+$aPages)) < $pages && $skipPage != TRUE ){
            $fullyAssigned = 1;
			
			//Update the fullyAssigned status of the document
			$statement = 'UPDATE `assignmentDocuments` SET `fullyAssigned'.$task.'`=? WHERE `did` = ?';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array($fullyAssigned, $docResult['did']));			
        }
		
        //Send the newly assigned pages filename, start page and end page
        return array('fileName' => $docResult['filename'], 'startPage' => $newPageResult['page'], 'endPage' =>($newPageResult['page']+$aPages-1));
    }

    //Send the previously assigned pages filename, start page and end page
    return array('fileName' => $pageResult['filename'], 'startPage' => $pageResult['pageStart'], 'endPage' => $pageResult['pageEnd']);
}

//Generate the diff of content for the C transcripts | Returns diff of Task A & B |If two assignments have the same filename this will collide!
function diffC($fileName, $page, $startPage){

    //Make sure the array is empty
    unset($transcriptText);

    //Make a new Database handle
    $dbHandle = dbConnection();

    //Grab the transcripts for this page for Task A
    $statement = 'SELECT `text`,`sid` FROM `studentTranscripts` WHERE `task` = ?  AND `filename` = ? AND `page` = ? ORDER BY `tid` DESC';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array("A", $fileName, $page));
    $transcriptResultA = $exec->fetch(PDO::FETCH_ASSOC);
	
	//Check if the transcript is done for A
	$statement = 'SELECT `completed` FROM `assignedPages` WHERE `sid` = ?  AND `filename` = ? AND `pageStart` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($transcriptResultA['sid'], $fileName, $startPage));
    $doneA = $exec->fetch(PDO::FETCH_ASSOC);

    //Remove most tags
    $transcriptResultA['text'] = strip_tags($transcriptResultA['text'], '<p>');

    //Remove nbsp
    $transcriptResultA['text'] = str_replace('&nbsp;', "", $transcriptResultA['text']);

    //Remove the </p> tags
    $transcriptResultA['text'] = str_replace("</p>", "", $transcriptResultA['text']);
    //Separate each p into an array item (each line)
    $transcriptResultA[] = explode("<p>", $transcriptResultA['text']);

    //Build the original div for comparision
    $return = '<div id="baseText" style="visibility:hidden; display:none;">';
    $first=TRUE;
    foreach($transcriptResultA[0] as $transcriptLine){
        if($first==FALSE){$return .=$transcriptLine.'&#13;';}
        else{$first=FALSE;}
    }
    $return .= '</div>';

    //Grab the transcripts for this page for Task B
    $statement = 'SELECT `text`,`sid` FROM `studentTranscripts` WHERE `task` = ?  AND `filename` = ? AND `page` = ? ORDER BY `tid` DESC';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array("B", $fileName, $page));
    $transcriptResultB = $exec->fetch(PDO::FETCH_ASSOC);
	
	//Check if the transcript is done for B
	$statement = 'SELECT `completed` FROM `assignedPages` WHERE `sid` = ?  AND `filename` = ? AND `pageStart` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($transcriptResultB['sid'], $fileName, $startPage));
    $doneB = $exec->fetch(PDO::FETCH_ASSOC);

    //Remove most tags
    $transcriptResultB['text'] = strip_tags($transcriptResultB['text'], '<p>');

    //Remove nbsp
    $transcriptResultB['text'] = str_replace('&nbsp;', "", $transcriptResultB['text']);

    //Replace the </p> tags
    $transcriptResultB['text'] = str_replace("</p>", "",$transcriptResultB['text']);
    //Separate each p into an array item (each line)
    $transcriptResultB[] = explode("<p>", $transcriptResultB['text']);

    //Build the second div for comparision
    $return .= '<div id="newText" style="visibility:hidden; display:none;">';
    $first=TRUE;
    foreach($transcriptResultB[0] as $transcriptLine){
        if($first==FALSE){$return .=$transcriptLine.'&#13;';}
        else{$first=FALSE;}
    }
    $return .= '</div>';

    //Include the output div
    $return .= '<div id="diffoutput"> </div>';

    //JSDiff Includes and initialization
    $return .= '<link rel="stylesheet" type="text/css" href="js/jsdifflib/diffview.css"/>
                <script type="text/javascript" src="js/jsdifflib/diffview.js"></script>
                <script type="text/javascript" src="js/jsdifflib/difflib.js"></script>

                <script type="text/javascript">

                function diffUsingJS(viewType) {
                    "use strict";
                    var byId = function (id) { return document.getElementById(id); },
                        base = difflib.stringAsLines(byId("baseText").innerHTML),
                        newtxt = difflib.stringAsLines(byId("newText").innerHTML),
                        sm = new difflib.SequenceMatcher(base, newtxt),
                        opcodes = sm.get_opcodes(),
                        diffoutputdiv = byId("diffoutput"),
                        contextSize = "";

                    diffoutputdiv.innerHTML = "";
                    contextSize = contextSize || null;

                    diffoutputdiv.appendChild(diffview.buildView({
                        baseTextLines: base,
                        newTextLines: newtxt,
                        opcodes: opcodes,
                        baseTextName: "Transcript A",
                        newTextName: "Transcript B",
                        contextSize: contextSize,
                        viewType: viewType
                    }));
                }
                window.onload = diffUsingJS();
                </script>';

	//If the transcripts are not done we can't show them. Come back later.
	if($doneA['completed'] !== "TRUE" || $doneB['completed'] !== "TRUE"){
		
		return FALSE;
	}
	else{

		//Otherwise show the transcripts					
		return $return;
	}
}

/*----------PDF FUNCTIONS----------*/
/*---------------------------------*/

//Take in a list of urls, check if they resolve, grab them, check if they are .pdf, split them, save them, remove original file
//Returns urls of successfully split pdfs
function pdfSplit($urls, $className){

    //Require the pdf manipulation library
    require_once("pdftk-toolkit/vendor/autoload.php");

    //Count for each foreach pass
    $count = 0;

	//Foreach URL do the following
	foreach($urls as $url) {

        //Get the name of the file
        $lastSlash = strrpos($url, "/");
        $fileName = substr($url, $lastSlash+1);

        //Initialize curl
        $ch = curl_init();

		//Set curl options
        set_time_limit(0); // unlimited max execution time
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        //Default status of our curl query will be false
        $data = FALSE;

        //Actually execute the grab
        $data = curl_exec($ch);
        curl_close($ch);

        //If we got something back (the url resolves)
        if($data !== FALSE){

			//Check if the directory exists for this class and if not make it - TMP
			if (!file_exists('tmp/'.$className.'')) {
				mkdir('tmp/'.$className.'', 0777, true);
			}

            //Write it to a file
            $fp = fopen('tmp/'.$className.'/'.$fileName.'', "w");
            fwrite($fp, $data);
            fclose($fp);

            //Check if it's actually a pdf
            if(mime_content_type('tmp/'.$className.'/'.$fileName.'') == "application/pdf") {

                //Make sure the filename actually ends in pdf and append if needed
                if (strpos($fileName, ".pdf") === FALSE){
                    //Rename the file
                    rename('tmp/'.$className.'/'.$fileName.'', 'tmp/'.$className.'/'.$fileName.'.pdf');

                    //Update our variable name
                    $fileName .=".pdf"; 
                }

                //Check if the directory exists for this class and if not make it - PDF
                if (!file_exists('pdfs/'.$className.'')) {
                    mkdir('pdfs/'.$className.'', 0777, true);
                }

                //Run forever until broken out of. With a running count variable.
                for($i=1; TRUE==TRUE; $i++) {

                    //Try to separate the PDF into an individual page but we don't know it's length
                    try {
                        //New Pdftk object (have to specifically call the namespace of Pdftk)
                        $oPdftk = new \Pdftk\Pdftk();
                        $oPdftk->setInputFile(array(
                            "filename" => 'tmp/' . $className . '/' . $fileName . '',
                            'start_page' => $i,
                            'end_page' => ($i)
                        ))->setOutputFile('pdfs/' . $className . '/' .$i.'-'.$fileName.'');
                        $oPdftk->_renderPdf();
                    }
                    //We've reached the end of the pdf. Break out
                    catch (Exception $e) {
                        //echo 'Message: ' . $e->getMessage();
                        break;
                    }
                }

                //Remove original tmp file
                unlink('tmp/'.$className.'/'.$fileName.'');

                //Append the good urls to return
                $successUrls[$count] = array('url' => $url, 'fileName' => $fileName, 'length' => ($i-1));
            }

            //Not a PDF, delete the file + folder
            else{
                unlink('tmp/'.$className.'');
				echo "<p>ERROR: URL does not resolve to a PDF file. Reload and try again.</p>";
            }
        }

        //Increment count
        $count++;
	}
    //Return a list of urls that were successfully processed in the format www.brocku.ca{-}www.google.ca{-} etc.
	return $successUrls;
}
