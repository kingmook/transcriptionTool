<?php
//Instructor Pages
//Created by: mbrousseau(kingmook) & ctoscher

//Include required specific tool functions
require_once("functions.php");

//Include required functions for pdf manipulation
require_once("pdftk-toolkit/vendor/autoload.php");

//include the LTI Library
require_once("LTI/functions/easyLTI.php");	

//Get ready for sessions
session_start();

//Grab our user info via LTI or built session, die if no LTI or session
$ltiObject = ltiSessionCheck();

//Minimum role allowed to view page (student, ta, or instructor)
authLevel("student", $ltiObject->info['ext_sakai_role'], false, $ltiObject);

//Default head builder
defaultHead("Dashboard");

?>
 <!--Initalize Tabs & Datapicker-->
<script>
	$(function() {
		$( "#tabs" ).tabs();
	});
	$(function(){$('.datepicker').datepicker({dateFormat: 'dd-mm-yy'}); });
</script>

<!--Include addInput form script-->
<script src="js/addInput.js" type="application/javascript"></script>

<!--JQuery UI Modal on submit new assignment-->
<script type="application/javascript">

    $(function() {
        $( "#dialog" ).dialog({
            autoOpen: false
        });

        $('#newAssignment').submit(function(e){
            $("#dialog").dialog( "open" );
            $("#opener").attr("disabled", true);
        });
    });

</script>

<!--Instructor Tabs-->
<!--Try and jump the parrent window to the top of the screen-->
<div id="tabs" onload="window.parent.parent.scrollTo(0,0)";>
	<ul>
		<li><a href="#tabs-1">Overview</a></li>
		<li><a href="#tabs-2">Assignment Config</a></li>
	</ul>
	<!--Overview-->
	<div id="tabs-1" style="height:900px;">
		
		<h3>Active Transcript Assignments</h3>
		
		<!--Grab the active transcripts assignments-->
		<?php
		//Connect to the information_schema DB
		$dbHandle = dbConnection();

		//Grab all the assignments associated with the course
		$statement = 'SELECT * FROM `assignmentInfo` WHERE `cid` = ?';
		$exec = $dbHandle->prepare($statement);
		$exec->execute(array($_SESSION['cid']));

		//Print out each assignment
		foreach ($exec->fetchAll(PDO::FETCH_ASSOC) as $assignment) {

			//For the first overview tab
			echo '
        <div class="taskWrapper">
        <h2>' . $assignment['name'] . '</h2>
        <div class="roundWrap">
        <div class="topTasks">
            <div class="taskA">
                <h3>TASK A - Transcript One</h3>
                <form action="grade.php" method="POST" name="taskATranscribe">
					<br /><br />
                    <input type="submit" name="taskAGrade" value="Review Student Submissions" class="ui-button ui-corner-all ui-state-default">
                    <input type="hidden" name="assignmentAid" value="'.$assignment['aid'].'">
                    <input type="hidden" name="assignmentName" value="'.$assignment['name'].'">
                    <input type="hidden" name="task" value="A">
                </form>
            </div>

            <div class="taskB">
                <h3>TASK B - Transcript Two</h3>
                <form action="grade.php" method="POST" name="taskBTranscribe">
					<br /><br />
                    <input type="submit" name="taskBGrade" value="Review Student Submissions" class="ui-button ui-corner-all ui-state-default">
                    <input type="hidden" name="assignmentAid" value="'.$assignment['aid'].'">
                    <input type="hidden" name="assignmentName" value="'.$assignment['name'].'">
                    <input type="hidden" name="task" value="B">
                </form>
            </div>
        </div>
        <div class="finalTask">
            <h3>TASK C - Transcript Comparison</h3>
            <form action="grade.php" method="POST" name="taskBTranscribe">
				<br /><br />
                <input type="submit" name="taskCGrade" value="Review Student Submissions" class="ui-button ui-corner-all ui-state-default">
                <input type="hidden" name="assignmentAid" value="'.$assignment['aid'].'">
                <input type="hidden" name="assignmentName" value="'.$assignment['name'].'">
                <input type="hidden" name="task" value="C">
            </form>
        </div>
        <div class="finalTask">
            <h3>Common Items</h3>
            <p>Required Pages: '.$assignment['numPages'].'</p>
            <p>Documents:  ';

            //Grab all the documents for this assignment
            $statement = 'SELECT * FROM `assignmentDocuments` WHERE `aid` = ?';
            $exec = $dbHandle->prepare($statement);
            $exec->execute(array($assignment['aid']));

            //Foreach document grabbed
            foreach ($exec->fetchAll(PDO::FETCH_ASSOC) as $document) {
                echo''.$document['filename'].'('.$document['length'].'), ';
            }


        echo '</p></div></div></div>';
		}
echo '			
	</div>
	
	
	<!--New Assignment / Update Assignment-->
	<div id="tabs-2">

        <div id="newForm">

            <form id="newAssignment" method="POST" action="update.php">

                <h2>Assignment Config</h2>
                <p>You can edit your existing assignment or create a new transcription assignment if one doesn\'t currently exist. All fields are required.</p>

                <h3>Assignment Name</h3>
                <p>A unique name for this transcription assignment.</p>
                <p>
                    <label for="assignmentName">Assignment Name</label>
                    <input name="assignmentName" id="assignmentName" type="text" value="'.$assignment['name'].'" required />
                </p>

                <h3>Opening Date</h3>
                <p>The starting and ending dates for all tasks in dd-mm-yyyy format.</p>
                <p>
                    <label for="dataPickStartA">Start Date&nbsp;&nbsp;</label>
                    <input name="taskAStart" type="text" id="dataPickStartA" class="datepicker" value="'.date("d-m-y", $assignment['taskAOpen']).'" required />
                </p>
                <p>
                    <label for="dataPickEndA">End Date&nbsp;&nbsp;&nbsp;</label>
                    <input name="taskAEnd" type="text" id="dataPickEndA" class="datepicker" value="'.date("d-m-y", $assignment['taskAClose']).'" required />
                </p>';
                
				//Print out the current URLS
                echo '<h3>Document URLs</h3>
                <p>These are the documents that will be used by your student\'s for transcription. Add full URLs to the documents (including http/https). The documents must be in PDF format.</p>
                <div id="urlDiv">
                    <p>
                        1 - <label for="url"><input type="url" id="url" size="60" name="url1" value="" value="https://www.brocku.ca/file.pdf" required /></label>
                    </p>
                </div>

                <a href="#" id="addUrl">Add Another Document (pdf)</a>';
				
				echo'
                <h3>Required Pages</h3>
                <p>The number of pages required per task for each student.</p>
                <p>
                    <label for="pages">Number of Pages</label>
                    <input name="pages" id="pages" type="number" value="'.$assignment['numPages'].'" min="1" required />
                </p>

                <!--Items for setting action-->
                <input type="hidden" value="createAssignment" name="formAction">

                <br /><input type="submit" value="Save" id="opener">
            </form>
        </div>';
?>	
        <!--Submitting modal for new assignments-->
        <div id="dialog" title="PDF Processing">
            <p>The PDF document(s) you've selected are being separated into individual pages. This process takes ~5 seconds per page. Larger documents can take several minutes.</p>
            <p>Please be patient.</p>
            <br /><br />
            <div id="processing">
                <img src="img/loading_transparent.gif">
                <p class="red">Processing...</p>
            </div>
        </div>
	
	</div>





