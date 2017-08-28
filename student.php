<?php
//Student Pages
//Created by: mbrousseau(kingmook) & ctoscher

//Include required specific tool functions
require_once("functions.php");

//Include required functions for pdf manipulation
require_once("../pdftk-toolkit/vendor/autoload.php");

//include the LTI Library
require_once("../LTI/functions/easyLTI.php");

//Get ready for sessions
session_start();

//Grab our user info via LTI or built session, die if no LTI or session
$ltiObject = ltiSessionCheck();

//Minimum role allowed to view page (student, ta, or instructor)
authLevel("student", $ltiObject->info['ext_sakai_role'], false, $ltiObject);

//Default head builder
defaultHead("Dashboard");

?>
<!--Initalize Tabs-->
<script>
  $(function() {
    $( "#tabs" ).tabs();
  });
</script>

<!--Initialize Datatables-->
<script>
    $(document).ready(function(){
        $('#assignmentTable').DataTable();
    });
</script>

<!--Student Tabs-->
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">Overview</a></li>
      <li><a href="#tabs-2">Help</a></li>
  </ul>
  <div id="tabs-1">

    <!--Grab the active transcripts assignments-->
    <?php
    //Connect to the information_schema DB
    $dbHandle = dbConnection();
	
	//Print Student Name
	echo "<p style=\"text-align:right;\">User: ".$_SESSION['_basic_lti_context']['ext_sakai_eid']."</p>";

    //Grab the CID of the class in question
    $statement = 'SELECT `cid` FROM `classInfo` WHERE `className` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($ltiObject->info['context_id']));
    $cidResult = $exec->fetch(PDO::FETCH_ASSOC);

    //Grab all the assignments associated with the course
	$statement = 'SELECT `aid`, `name`, `numPages` FROM `assignmentInfo` WHERE `cid` = ?';
    $exec = $dbHandle->prepare($statement);
    $exec->execute(array($cidResult['cid']));

    //Print out the status update, if any, from GET
    echo '<p id="notice">'.checkStatus($_GET['status']).'</p>';

    //Print out each assignment
    foreach ($exec->fetchAll(PDO::FETCH_ASSOC) as $assignment){

		//Check if the student has finished task A & B
        $statement = 'SELECT `completed`, `task` FROM `assignedPages` WHERE `aid` = ? AND `sid` = ? AND `task`=?';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($assignment['aid'], $_SESSION['sid'], "A"));
        $completedAResult = $exec->fetch(PDO::FETCH_ASSOC);

        $statement = 'SELECT `completed`, `task` FROM `assignedPages` WHERE `aid` = ? AND `sid` = ? AND `task`=?';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($assignment['aid'], $_SESSION['sid'], "B"));
        $completedBResult = $exec->fetch(PDO::FETCH_ASSOC);
	
	
		//Messages for if A is completed or not
		if ($completedAResult['completed'] == "TRUE"){
			$messageA = '<p class="green">Task A Completed</p>';
			$aButton = "Review";
		}
		else{
			$messageA = '<p class="red">Task A Incomplete</p>';
			$aButton = "Transcribe";
		}
		
		//Messages for if B is completed or not
		if ($completedBResult['completed'] == "TRUE"){
			$messageB = '<p class="green">Task B Completed</p>';
			$bButton = "Review";
		}
		else{
			$messageB = '<p class="red">Task B Incomplete</p>';
			$bButton = "Transcribe";
		}
	
        //For the first overview tab
        echo'
        <div class="taskWrapper">
        <h2>'.$assignment['name'].'</h2>
        <div class="roundWrap">
        <div class="topTasks">
            <div class="taskA">
                <h3>TASK A - Transcript One</h3>
				<p>'.$messageA.'</p>
                <form action="transcribe.php" method="POST" name="taskATranscribe">
                    <input type="submit" name="taskATranscribe" value="'.$aButton.'" class="ui-button ui-corner-all ui-state-default">
                    <input type="hidden" name="assignmentAid" value="'.$assignment['aid'].'">
                    <input type="hidden" name="assignmentName" value="'.$assignment['name'].'">
                    <input type="hidden" name="task" value="A">
                </form>
            </div>

            <div class="taskB">
                <h3>TASK B - Transcript Two</h3>
				<p>'.$messageB.'</p>
                <form action="transcribe.php" method="POST" name="taskBTranscribe">
                    <input type="submit" name="taskBTranscribe" value="'.$bButton.'" class="ui-button ui-corner-all ui-state-default">
                    <input type="hidden" name="assignmentAid" value="'.$assignment['aid'].'">
                    <input type="hidden" name="assignmentName" value="'.$assignment['name'].'">
                    <input type="hidden" name="task" value="B">
                </form>
            </div>
        </div>
        <div class="finalTask">
        <h3>TASK C - Transcript Comparison</h3>
        <form action="transcribe.php" method="POST" name="taskBTranscribe">';



		//If they have completed both Task A & B
        if($completedAResult['completed'] == "TRUE" && $completedBResult['completed'] == "TRUE"){
			
			//Check if they have completed C
			$statement = 'SELECT `completed`, `task` FROM `assignedPages` WHERE `aid` = ? AND `sid` = ? AND `task`=?';
			$exec = $dbHandle->prepare($statement);
			$exec->execute(array($assignment['aid'], $_SESSION['sid'], "C"));
			$completedCResult = $exec->fetch(PDO::FETCH_ASSOC);
			
			//Messages for if B is completed or not
			if ($completedCResult['completed'] == "TRUE"){
				$messageC = '<p class="green wide">Task C Completed</p>';
				$cButton = "Review";
			}
			else{
				$messageC = '<p class="red wide">Task C Incomplete</p>';
				$cButton = "Transcribe";
			}
			
			echo '<p>'.$messageC.'</p>';
			echo '<input type="submit" name="taskCTranscribe" value="'.$cButton.'" class="ui-button ui-corner-all ui-state-default">';
			
        }
        else {
            echo '<p class="red">Complete Task A & Task B first</p>';
        }

        echo '<input type="hidden" name="assignmentAid" value="'.$assignment['aid'].'">
              <input type="hidden" name="assignmentName" value="'.$assignment['name'].'">
             <input type="hidden" name="task" value="C">
        </form>
        </div>
        </div>
        </div>';
    }

    ?>
  </div>

   <!--Student Grade Overview-->
   <!--<div id="tabs-2">

       <?php
       //Grab all the assignments associated with the course
	   $statement = 'SELECT `aid`, `name`, `numPages` FROM `assignmentInfo` WHERE `cid` = ?';
       $exec = $dbHandle->prepare($statement);
       $exec->execute(array($cidResult['cid']));

       //Print out each assignment
       foreach ($exec->fetchAll(PDO::FETCH_ASSOC) as $assignment){
       echo'
        <div class="taskWrapper">
        <h2>'.$assignment['name'].'</h2>
        <div class="roundWrap">
        <div class="topTasks">
            <div class="taskA">
                <h3>TASK A - Transcript One</h3>
                <p>10/10 - 100%</p>

            </div>

            <div class="taskB">
                <h3>TASK B - Transcript Two</h3>
                <p>7/10 - 70%</p>
            </div>
        </div>
        <div class="finalTask">
                <h3>TASK C - Transcript Comparison</h3>
                <p class="red">NOT YET MARKED</p>
        </div>
        </div>
        </div>';}
       ?>
   </div> -->

   <!--Help Tab-->
   <div id="tabs-2">
       <h2>Help</h2>

       <p>If you're having trouble with the assigned trancription component please contact the course instructors</p>
       <p>&nbsp;</p>
       <p>Technical concerns can be forwarded to <a href="mailto:edtech@brocku.ca">edtech@brocku.ca</a></p>


   </div>

</div>