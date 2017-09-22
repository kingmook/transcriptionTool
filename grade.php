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

//Create a DB Handle
$dbHandle = dbConnection();

?>

<!--Initalize Datatables-->
<script>
    $(document).ready(function(){
        $('#studentList').DataTable();
    });
    //Removed the tab click on the second tabs so they can go to external resources
    $(function() {
        $( "#tabs" ).tabs({ active: 2 });
        $("li.external a").unbind('click');

    });
</script>

<!--Grading Tabs and return to instructor.php for overview-->
<div id="tabs">
    <ul>
        <li class="external"><a href="instructor.php">Overview</a></li>
        <li class="external"><a href="instructor.php#tabs-2">Assignment Config</a></li>
        <li><a href="#tabs-1">Grading</a></li>
    </ul>
    <!--Grading Tab-->
    <div id="tabs-1">


        <?php

        if(isset($_POST['markGrade'])) {

            echo '
        <div class="transcriptWrapper">
        <h2>Transcription</h2>

        <!--Left Side Document-->
        <div class="document">
            <div class="theFrame">

        Original Document
        <iframe height="100" width="100" src="https://www.brocku.ca/webfm_send/236"></iframe>

            </div>

        </div>



            <!--Right Side Transcript-->

            <div class="transcript">

                <label for="transciptText">Transcription Text</label><br />
                <textarea id="transciptText" name="transciptText" rows="40" cols="50"></textarea>

            </div>

            <form method="POST" action="grade.php" name="transcriptionForm">
                <input type="submit" value="Discard Changes" name="transciptCancel">
                <input type="submit" value="Save" name="transciptSave">
            </form>

        </div>

        ';
        }


        ?>

    <table id="studentList" class="display" cellspacing="0" width="100%">
        <thead>
        <tr>
            <th>Name</th>
            <th>Campus ID</th>
            <th>Submissions</th>
            <th>Complete</th>
            <th></th>
        </tr>
        </thead>

        <tbody>

        <?php

        echo '<p></p>';
        echo '<h2>Submissions to '.$_POST['assignmentName'].' - Task '.$_POST['task'].'</h2><p>&nbsp;</p>';

        //The the class info for the assignment in question
        $statement = 'SELECT `cid` FROM `assignmentInfo` WHERE `aid` = ?';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($_POST['assignmentAid']));
        $cid = $exec->fetch(PDO::FETCH_ASSOC);

        //Grab all the students associated with this assignment except for the sid 0
        $statement = 'SELECT * FROM `userInfo` WHERE `cid` = ? AND `role` = ? AND NOT `sid` = ?';
        $exec = $dbHandle->prepare($statement);
        $exec->execute(array($cid['cid'], "Student", 0));

        //Print out each student in the assignment
        foreach ($exec->fetchAll(PDO::FETCH_ASSOC) as $student) {

            echo '<tr>
            <th>'.$student['fullName'].'</th>
            <th>'.$student['campusId'].'</th>';

            //Check if the student has a completed transcript
            $statement = 'SELECT `completed`, `completedDate`, `pid` FROM `assignedPages` WHERE `aid` = ? AND `sid` = ? AND `task` = ?';
            $exec = $dbHandle->prepare($statement);
            $exec->execute(array($_POST['assignmentAid'],$student['sid'], $_POST['task']));
            $completedResult = $exec->fetch(PDO::FETCH_ASSOC);

            //If the grade check comes back false they have no grade
            if(!$gradeResult){
                $gradeResult['grade'] = "Incomplete";
            }

            //If they have submitted
            if($completedResult['completed'] == "TRUE"){

                echo '
                <th>Submitted: '.gmdate("d-M-Y", $completedResult['completedDate']).'</th>
                <th>Complete</th>
                <th>
			        <!--<form action="">-->
                    <form action="transcribeGrade.php" method="POST">
                    <input type="submit" name="markGrade" value="Review">
                </th>';

            }
            else {
                echo '
                <th>No Submission</th>
                <th>'.$gradeResult['grade'].'</th>
                <th>
                    <form action="transcribeGrade.php" method="POST">
                    <input type="submit" name="markGrade" value="Review">
                </th>';
            }

            echo '  <input type="hidden" name="studentGrade" value="'.$student['sid'].'">
                    <input type="hidden" name="assignmentAid" value="'.$_POST['assignmentAid'].'">
                    <input type="hidden" name="task" value="'.$_POST['task'].'">
                </form>
            </th>
        </tr>';

        }

        ?>
        </tbody>
    </table>

        <p>&nbsp;</p>
        <a href="instructor.php">Back to Overview</a>

    </div>

</div>

</body>
</html>
