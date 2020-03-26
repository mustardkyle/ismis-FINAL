<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="bootstrap/bootstrap.min.css">
</head>
<body>
    <?php
        session_start();
        if(!isset($_SESSION['user']) || $_SESSION['user']['type'] != 2){
            $_SESSION['error'] = 'Invalid Session!';
            header("Location: login.php");
        }

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ismis";
        $user_type = $_SESSION['user']['type'];
        // 1 is faculty, 2 is student
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <a class="navbar-brand" href="#">Ismisismisismis</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarColor01">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <form action="admin.php" method="post">
                        <button type="submit" class="btn btn-primary" name='logout'>Logout</button>
                    </form>
                    <?php
                        if(isset($_POST['logout'])){
                            unset($_SESSION['user']);
                            $_SESSION['error'] = '';
                            header("Location: login.php");
                        }
                    ?>
                </li>
            </ul>
        </div>
    </nav>
    <div class="card border-primary mb-3" style="width: 80%; margin: auto; margin-top:30px;">
    <div class="card-body">

        <?php
            echo "<h5>Welcome, ".ucfirst($_SESSION['user']['firstname']). " ".ucfirst($_SESSION['user']['lastname'])."</h5><hr>";
            $sql = "SELECT * FROM subjects_enrolled WHERE user_id=".$_SESSION['user']['id'];
            $study_load = $conn->query($sql);
            $enrolled_subjects = [];
            $enrolled_schedules = [];
            if($study_load!=null && $study_load->num_rows > 0){
                while($schedule_details = $study_load->fetch_assoc()){
                    // fetch course details
                    $sql = "SELECT * FROM subjects WHERE id=".$schedule_details['subject_id'];
                    $subject_details = ($conn->query($sql))->fetch_assoc();
                    array_push($enrolled_subjects, $subject_details);
                    // fetch teacher details
                    $sql = "SELECT * FROM users WHERE id=".$schedule_details['user_id'];
                    $teacher_details = ($conn->query($sql))->fetch_assoc();
                    // fetch schedule details
                    $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$schedule_details['schedule_id'];
                    $schedule_week = $conn->query($sql);

                    echo "
                    <form action='student.php' method='post'>
                    <table class='table table-hover'>
                        <tr>
                            <th>Group #</th>
                            <th>Course id</th>
                            <th>Course name</th>
                            <th>Schedule</th>
                            <th></th>
                        </tr>
                        <tr>
                            <td>".$schedule_details['schedule_id']."</td>
                            <td>".$subject_details['id']."</td>
                            <td>".$subject_details['name']."</td>
                            <td>";
                    while($schedule_day = $schedule_week->fetch_assoc()){
                        array_push($enrolled_schedules, $schedule_day);
                        echo $schedule_day['timeday']." ".$schedule_day['timein']."-".$schedule_day['timeout']." ";
                    }
                    echo "</td>
                        <td><button class='btn btn-outline-danger' type='submit' name='unenroll' value='".$schedule_details['schedule_id']."'>Unenroll</td>
                        </tr>
                    ";
                }
            } else {
                echo "<label>No subjects enrolled!</label><br>";
            }
        ?>
        </table>

        <?php
            $sql = "SELECT * FROM subjects ";
            if($enrolled_subjects != []){
                $sql = $sql."WHERE id NOT IN (";
                for($count=0; $count<sizeof($enrolled_subjects); $count++){
                    $sql = $sql."'".$enrolled_subjects[$count]['id']."'";
                    if( ($count+1)!=sizeof($enrolled_subjects) ){
                        $sql = $sql.", ";
                    }
                }
                $sql = $sql.");";
            }
            // $sql result will return all subjects that can be enrolled,
            // you still have to check the schedule to see if it conflicts

            $can_enroll = $conn->query($sql);
            if($can_enroll != null && $can_enroll->num_rows>0){
                $req_subjects = [];
                // req schedule will contain all schedules of subjects that can be enrolled
                while($subject_details = $can_enroll->fetch_assoc()){
                    array_push($req_subjects, $subject_details);
                }
                if($req_subjects != []){
                    echo "
                        <form input='student.php' method='post'>
                            <br><label>Enroll a subject?</label>
                            <select name='subject' class='custom-select' style='width: 20%'>
                                ";
                    for($count=0; $count<sizeof($req_subjects); $count++){
                        echo "<option value='".$req_subjects[$count]['id']."'>".$req_subjects[$count]['name']."</option>";
                    }
                    echo "
                            </select>
                            <button class='btn btn-outline-success' type='submit' name='enroll_subject'>Check Schedule</button>
                        </form><br>
                    ";
                } else {
                    echo 'No subjects can be enrolled!';
                }

            } else {
                echo "<label>No subjects can be enrolled with current schedule!</label>";
            }

            if(isset($_POST['enroll_subject'])){
                // $_POST['subject'] contains subject ID
                $schedules_enroll = [];
                $sql = "SELECT * FROM subjects_schedules WHERE subject_id=".$_POST['subject'];
                $subject_schedules = $conn->query($sql);
                if($subject_schedules!=null && $subject_schedules->num_rows > 0){
                    while($subject_details = $subject_schedules->fetch_assoc()){
                        $subject_details['eligible'] = true;
                        $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$subject_details['schedule_id'];
                        $schedule_list = $conn->query($sql);
                        while(($schedule = $schedule_list->fetch_assoc()) && $subject_details['eligible'] == true){
                            for($count=0; $count<sizeof($enrolled_schedules) && $subject_details['eligible'] == true; $count++){
                                if($enrolled_schedules[$count]['timeday'] == $schedule['timeday']){
                                    $timein = $schedule['timein'];
                                    $timeout = $schedule['timeout'];
                                    $req_timein = $enrolled_schedules[$count]['timein'];
                                    $req_timeout = $enrolled_schedules[$count]['timeout'];
                                    if($timein >= $req_timein && $timein < $req_timeout){
                                        $subject_details['eligible'] = false;
                                    }
                                    if($timeout > $req_timein && $timeout <= $req_timeout){
                                        $subject_details['eligible'] = false;
                                    }
                                }
                            }
                        }
                        if($subject_details['eligible'] == true){
                            array_push($schedules_enroll, $subject_details);
                        }
                        
                    }
                    if($schedules_enroll != []){
                        echo "
                            <form method='POST' action='student.php'>
                                <table class='table table-hover'>
                                    <tr>
                                        <td>Group #</td>
                                        <td>Course_id</td>
                                        <td>Course_name</td>
                                        <td>Schedule</td>
                                        <td></td>
                                    </tr>";
                        for($count=0; $count<sizeof($schedules_enroll); $count++){
                            $sql = "SELECT * FROM subjects WHERE id=".$schedules_enroll[$count]['subject_id'];
                            $subject = ($conn->query($sql))->fetch_assoc();
                            echo "
                                <tr>
                                    <td>".$schedules_enroll[$count]['schedule_id']."</td>
                                    <td>".$subject['id']."</td>
                                    <td>".$subject['name']."</td>
                                    <td>";
                            $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$schedules_enroll[$count]['schedule_id'];
                            $schedule_list = $conn->query($sql);
                            while($schedule = $schedule_list->fetch_assoc()){
                                echo $schedule['timeday']." ".$schedule['timein']."-".$schedule['timeout']."<br>";
                            }
                            echo "
                                    </td>
                                    <input type='hidden' value='".$subject['id']."' name='subject_id'>
                                    <td><button type='submit' class='btn btn-outline-success' value='".$schedules_enroll[$count]['schedule_id']."' name='enroll_sched'>Enroll</button></td>
                                </tr>
                            ";
                        }
                        echo "
                                </table>
                            </form>
                        ";
                    } else {
                        echo "<br><label>No schedules available to enroll!</label>";
                    }
                } else {
                    echo "<br><label>No schedules available to enroll!</label>";
                }
            }
        ?>
    </form>
    
    <br>
    <?php
        if(isset($_POST['unenroll'])){
            $sql = "DELETE FROM subjects_enrolled WHERE user_id=".$_SESSION['user']['id']." AND schedule_id=".$_POST['unenroll'];
            $conn->query($sql);
            header("Location: student.php");
        }

        if(isset($_POST['enroll_sched'])){
            $user_id = $_SESSION['user']['id'];
            $schedule_id = $_POST['enroll_sched'];
            $subject_id = $_POST['subject_id'];
            $sql = "INSERT INTO subjects_enrolled(user_id, subject_id, schedule_id) 
                    VALUES('$user_id', '$subject_id', '$schedule_id')";
            $conn->query($sql);
            header("Location: student.php");
        }
    ?>
    </div>
    </div>
</body>
</html>