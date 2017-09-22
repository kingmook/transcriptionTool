<?php
//Include required specific tool functions
require_once("functions.php");

//Include required functions for pdf manipulation
require_once("pdftk-toolkit/vendor/autoload.php");

//include the LTI Library
require_once("LTI/functions/easyLTI.php");

//Get ready for sessions
session_start();

//Default head builder
defaultHead("Dashboard");

//Grab our user info via LTI or built session, die if no LTI or session
$ltiObject = ltiSessionCheck();

//Minimum role allowed to view page (student, ta, or instructor)
authLevel("instructor", $ltiObject->info['ext_sakai_role'], false, $ltiObject);

//Include the ckeditor support code
echo '<script src="js/ckeditor/ckeditor.js"></script>';

//If it's an instructor coming in, we need the student's sid not the instructors sid
if ($_SESSION['role'] != "Student"){
	$_SESSION['sid'] = $_POST['studentGrade'];	
}	

//Only assign pages if the user is a student
//Assign required number of unique pages to student if not already done for the specific task
if(!$pages = assignedPages($_POST['assignmentAid'], $_SESSION['sid'], $_POST['task'], $_SESSION['role'])){echo "No more pages to assign. Please contact the course Instructor.";}


//Default starting pages
$defaultPage = $pages['startPage'];

//Current page
$page = $defaultPage;

//Check if the transcript has already been submitted for grading
$dbHandle = dbConnection();

//See if the student already has assigned pages
$statement = 'SELECT `completed` FROM `assignedPages` WHERE  `sid` = ? AND `task` = ? AND `filename` = ?';
$exec = $dbHandle->prepare($statement);
$exec->execute(array($_SESSION['sid'], $_POST['task'], $pages['fileName']));
$transcriptComplete = $exec->fetch(PDO::FETCH_ASSOC);


//Grab the content from last time the transcript was edited but only for the first page, the other pages will come from ajax
//See if the student already has assigned pages
$statement = 'SELECT `text` FROM `studentTranscripts` WHERE `sid` = ? AND `task` = ? AND `filename` = ? AND `page` = ? ORDER BY `tid` DESC';
$exec = $dbHandle->prepare($statement);
$exec->execute(array($_SESSION['sid'], $_POST['task'], $pages['fileName'],$page));
$pageResult = $exec->fetch(PDO::FETCH_ASSOC);

echo "<!-- DEBUG- ".$_SESSION['sid']." | ".$_POST['task']." | ".$pages['fileName']." | ".$pageResult['text']." | ".$_SESSION['role']." -->";

//The text pulled from the last time the student worked on this assignment sorted by most recent entry
$lastText = $pageResult['text'];

//Open the description modal if the user is a students and it's their first time seeing the modal

if($_SESSION['role'] == "Student"){

	//If we're on task C and their are not enough transcripts to complete the task
	if($_POST['task'] == "C"){
	
		$responseC = diffC($pages['fileName'], $page, $defaultPage);
		if ($responseC == FALSE){
			echo "<h2>Task C - Not Ready</h2>";
			echo "<p>Completed Task A & Task B transcripts are not yet available for this task.</p> <p>You will need to wait until your peers have completed their transcripts before you can complete Task C. Please come back later.</p>";
			echo '<form method="POST" action="update.php" name="transcriptionForm">';
			echo '<input type="submit" value="Back" name="transcriptCancel" class="ui-button ui-corner-all ui-state-default">';
			echo '</form>';
			die;
		}
	}



    $task = 'task'.$_POST['task'].'Info';

    $statement = 'SELECT `'.$task.'` FROM `userInfo` WHERE  `sid` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($_SESSION['sid']));
    $modalView = $exec->fetch(PDO::FETCH_ASSOC);

    if($modalView[''.$task.''] == "FALSE"){

        //Print out the modal support script
        echo '  <script>
                  $(function() {
                    $( "#dialog" ).dialog();
                  });
                </script>';


        if($_POST['task'] == "A" || $_POST['task'] == "B"){

            echo '
            <div id="dialog" title="Task Instructions">
              <p>For task '.$_POST['task'].' you are required to complete a transcription of a source document. The source document is located on the left of the
              page. Your transcript of it will be on the right. The transcript will be completed one page at a time. When you are finished with the current page
              choose "Next" at the top to move to the next page. You can also choose "Previous" to go back and review your work.
              </p>

              <p>The buttons at the bottom of the page will allow you to:
               <ul>
                   <li>Go "Back" to the assignment Overview page.</li>
                   <li>"Save" your current work and go back to the Overview page.</li>
                   <li>"Save and Mark as Done" to submit your transcript for review. You will no longer be able to make edits after marking as done.</li>
               </ul>
               <div class="centre">
                   <button onclick=\'$( "#dialog" ).dialog("close")\' class="ui-button ui-corner-all ui-state-default" id="okButton">OK</button>
               </div>
               </p>
            </div>';
        }
        elseif($_POST['task'] == "C"){

            echo '
            <div id="dialog" title="Task Instructions">
              <p>For task '.$_POST['task'].' you are required to complete a transcription of a source document. The source document is located on the left of the
              page. Your transcript of it will be on the right. The transcript will be completed one page at a time. When you are finished with the current page
              choose "Next" at the top to move to the next page. You can also choose "Previous" to go back and review your work.
              </p>

              <p>The buttons at the bottom of the page will allow you to:
               <ul>
                   <li>Go "Back" to the assignment Overview page.</li>
                   <li>"Save" your current work and go back to the Overview page.</li>
                   <li>"Save and Mark as Done" to submit your transcript for review. You will no longer be able to make edits after marking as done.</li>
               </ul>
               <div class="centre">
                   <button onclick=\'$( "#dialog" ).dialog("close")\' class="ui-button ui-corner-all ui-state-default" id="okButton">OK</button>
               </div>
               </p>
            </div>';
        }


        //Student has seen the modal, don't show it again.
        $statement = 'UPDATE `userInfo` SET `'.$task.'`=? WHERE  `sid` = ?';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array("TRUE", $_SESSION['sid']));
    }
}


//Print out the transcription body
echo'
    <body>
        <div id="transcriptWrapper">
        <h2>Transcription Task '.$_POST['task'].'</h2>



        <!--Left Side Document-->
        <div class="document">
            <div class="theFrame">

                <p>Original Document</p>
                <div class="pageControl">
                    <button class="ui-button ui-corner-all ui-state-default" onclick="goBack();">Previous</button>
                    <p>Page <span id="currentPage1">'.($page-$pages['startPage']+1).'</span> of <span id="maxPage">'.($pages['endPage']-$pages['startPage']+1).'</span></p>
                    <button class="ui-button ui-corner-all ui-state-default" onclick="goForward();">Next</button>
                </div>';

            //Print out the iFrame for Task A & B
            if($_POST['task']=="A" || $_POST['task'] == "B") {

                echo '<iframe id="transcriptFrame" height="100" width="100" src="pdfs/' . $ltiObject->info["context_id"] . '/' . $page . '-' . $pages['fileName'] . '"></iframe>';
            }
            elseif($_POST['task'] == "C"){
                //Output the initial diff between transcripts for the appropriate page
                echo '<div id = "diffFrame">';
				
					//Get the initial diff C response
					$responseC = diffC($pages['fileName'], $page, $defaultPage);
					
					//If it's false we don't have two completed transcripts
					if ($responseC == FALSE){ echo "Completed Task A & Task B transcripts are not yet available for this task. Please come back later.";}
					else{ 
						echo $responseC;				
					}


                echo '</div>';

                echo '<iframe id="transcriptFrame" class="transcriptFrameHalf" height="50" width="100" src="pdfs/' . $ltiObject->info["context_id"] . '/' . $page . '-' . $pages['fileName'] . '"></iframe>';

            }

            echo '</div>

        </div>

            <!--Right Side Transcript-->
            <form method="POST" action="update.php" name="transcriptionForm">
                <div class="transcript">';

                    //If the transcript has been submitted for grading
                    if($transcriptComplete['completed'] == "TRUE"){
                        echo '<label for="transcriptText">Transcription Text (Marked as Done - Cannot be Edited)</label>';
                    }
                    //If not
                    else {
                        echo '<label for="transcriptText">Transcription Text</label>';
                    }

                    echo'<br /><br /><br />
                    <!--Add the text from last time the transcript was accessed-->
                    <textarea id="transcriptText" name="transcriptText" rows="40" cols="50">
                    '.$lastText.'
                    </textarea>

                </div>

                <div class="transcriptForm">
                        ';

                        //If an Instructor or TA is grading
                        if($_POST['markGrade'] == "Review") {

                            /* THEY AREN'T ACTUALLTY DOING GRADES AND COMMENTS BUT THIS WORKS IF THEY CHANGE THEIR MINDS
                            //Grab any existing comments and/or grades the transcript already has
                            $statement = 'SELECT `grade`, `comment` FROM `grading` WHERE `sid` = ? AND `task` = ? ORDER BY `gid` DESC';
                            $exec = $dbHandle->prepare($statement);
                            $exec->execute(array($_SESSION['sid'], $_POST['task']));
                            $gradeResult = $exec->fetch(PDO::FETCH_ASSOC);


                            echo '  <div id="gradingForm">
                                        <label for="gradeComments">Comments</label><br />
                                        <textarea name="gradeComments" rows="10" cols="60">'.$gradeResult['comment'].'</textarea><br/ ><br />
                                        <label for"gradeNumber">Grade</label>
                                        <input type="number" name="gradeNumber" maxlenth="5" value="'.$gradeResult['grade'].'"/>
                                    </div>
                            echo '<input type="submit" value="Save Grading" name="transcriptCancel" class="ui-button ui-corner-all ui-state-default">';
                            ';*/


                            echo '<input type="submit" value="Back to Overview" name="transcriptCancel" class="ui-button ui-corner-all ui-state-default">';


                        }
                        else{
                            echo '<input type="submit" value="Back" name="transcriptCancel" class="ui-button ui-corner-all ui-state-default">';
                        }

                        if ($transcriptComplete['completed'] == "FALSE" && $_POST['markGrade']!=="Grade" && $_SESSION['role']=="Student") {
                            echo '<input type = "submit" value = "Save" name = "transcriptSave" class="ui-button ui-corner-all ui-state-default" >
                        <input type = "submit" value = "Save and Mark as Done" name = "transcriptFinal" class="ui-button ui-corner-all ui-state-default" >';
                        }

                        echo'
                        <input type="hidden" id="className" name="className" value="'.$ltiObject->info["context_id"].'" />
                        <input type="hidden" id="sid" name="sid" value="'.$_SESSION['sid'].'" />
                        <input type="hidden" id="task" name="task" value="'.$_POST['task'].'" />
                        <input type="hidden" id="filename" name="filename" value="'.$pages['fileName'].'" />
                        <input type="hidden" id="page" name="page" value="'.$page.'" />
						<input type="hidden" id="startPage" name="startPage" value="'.$page.'" />
                        <input type="hidden" id="aid" name="aid" value="'.$_POST['assignmentAid'].'" />


                </div>
            </form>

        </div>';

        //Make the ckeditor readonly. We don't want to make changes.
		echo '
		<!-- Initialize the ckeditor textarea-->
		<script>
			// Replace the <textarea id="editor1"> with a CKEditor
			// instance, using default configuration.
			CKEDITOR.config.customConfig = \'config.js\';
			CKEDITOR.replace( \'transcriptText\',{height:"770", readOnly:"true"});
		</script>';




//Change pdf page functions
echo'
<script>
    i = 0;
    //Go back to the previous pdf page
    function goBack(){

        //Get the current page
        currentPage = $("#page").val();

        //Make sure were not going back farther than our min pages
        if (currentPage > '.$pages['startPage'].'){

            //Get the name of the course
            className = $("#className").val();

            //Get the file name
            fileName = $("#filename").val();

            //Previous page
            prePage = parseInt(currentPage)-1;

            //Current page is adding 1 as a string not as a number
            document.getElementById(\'transcriptFrame\').src=\'pdfs/\'+className+\'/\'+(prePage)+\'-\'+fileName;

            //Decrease the value of the current page input
            document.getElementById(\'page\').value=prePage;

            //Decrease the value of the current page display
            document.getElementById(\'currentPage1\').innerHTML=prePage-'.$pages['startPage'].'+1;

           //Actually update the textarea text and when finished update the diffArea if task C
            $.when( updateText(parseInt(currentPage)))
              .done(function() {
                 updateDiff();
                 diffUsingJS();
              });

        }
    }

    function goForward(){
        //Get the current page
        currentPage = $("#page").val();

        //Make sure were not going back farther than our min pages
        if (currentPage < '.$pages['endPage'].'){

            //Get the name of the course
            className = $("#className").val();

            //Get the file name
            fileName = $("#filename").val();

            //Next page
            nextPage = parseInt(currentPage)+1;

            //Current page is adding 1 as a string not as a number
            document.getElementById(\'transcriptFrame\').src=\'pdfs/\'+className+\'/\'+(nextPage)+\'-\'+fileName;

            //Increase the value of the current page input
            document.getElementById(\'page\').value=nextPage;

            //Increase the value of the current page display
            document.getElementById(\'currentPage1\').innerHTML=nextPage-'.$pages['startPage'].'+1;

            //Actually update the textarea text and when finished update the diffArea if task C
            $.when( updateText(parseInt(currentPage)))
              .done(function() {
                 updateDiff();
                 diffUsingJS();
              });
        }
    }

    function updateText(currentPage){

     $.ajax({
                async: false,
                type: "POST",
                url: "update.php",
                data: {action: "textArea", sid: $("#sid").val(), task: $("#task").val(), filename: $("#filename").val(), page:$("#page").val(), currentpage:currentPage } ,
                success: function(msg){
                    CKEDITOR.instances[\'transcriptText\'].setData(msg);
console.log("Here");		    
console.log(msg);
console.log("Here");
                }
            }); // Ajax Call
     }

    function updateDiff(){
        //This is updating both the ckeditor and the document.getElement
        //Grab the Task C diff content
        if ($("#task").val() == "C"){
            $.ajax({
                        async: false,
                        type: "POST",
                        url: "update.php",
                        data: {action: "diffC", filename: $("#filename").val(), page:$("#page").val(), startPage:$("#startPage").val() } ,
                        success: function(msg){
								document.getElementById(\'diffFrame\').innerHTML=msg;
								console.log(msg);
                        }
            }); // Ajax Call
        }
    }


</script>
';

echo '</body></html>';


?>
