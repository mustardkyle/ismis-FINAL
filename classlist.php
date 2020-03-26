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
        if(!isset($_SESSION['user']) || $_SESSION['user']['type'] != 0){
            $_SESSION['error'] = 'Invalid Session!';
            header("Location: login.php");
        }

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ismis";

        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        if(!isset($_SESSION['schedule_id'])){
            header("Location: admin.php");
        }?>
        
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <a class="navbar-brand" href="#">Ismisismisismis</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarColor01">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item active">
        <a class="nav-link" href="#">Admin <span class="sr-only">(current)</span></a>
      </li>
            </ul>
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
        <?php

        $sql = "SELECT * FROM subjects_schedules WHERE schedule_id=".$_SESSION['schedule_id']." LIMIT 1";
        $details = ($conn->query($sql))->fetch_assoc();
        $schedule_id = $details['schedule_id'];
        // details contains data from subjects_schedules
        $sql = "SELECT * FROM subjects WHERE id=".$details['subject_id'];
        $subject_details = (($conn->query($sql))->fetch_assoc());
        $subject_name = $subject_details['name'];
        $sql = "SELECT * FROM users WHERE id=".$details['professor_id'];
        $professor = ($conn->query($sql))->fetch_assoc();
        $sql = "SELECT * FROM schedules_details WHERE schedule_id=$schedule_id";
        $subject_schedule = $conn->query($sql);
        $sql = "SELECT * FROM subjects_enrolled WHERE schedule_id=$schedule_id";
        $num_stud = ($conn->query($sql))->num_rows;
        // subject_schedule is important later when checking users
        ?>
        <div class="card border-primary mb-3" style="margin: auto; width: 50%; margin-top: 30px">
        <div class='card-header'><label>Viewing classlist for <?php echo $subject_name?></label></div>
        <div class="card-body">
        <?php
        echo "
            <label><strong>Schedule:</strong>";
        while($day = $subject_schedule->fetch_assoc()){
            echo $day['timeday']." ".$day['timein']." - ".$day['timeout']." ";
        }     
        echo "</label><br>
        <label><strong>Taught by: </strong>".$professor['firstname']." ".$professor['lastname']."</label><br>
        <label><strong>Population:</strong> $num_stud/".$details['max_pop']."</label><br><br>
        ";

        $sql = "SELECT * FROM subjects_enrolled WHERE schedule_id=".$_SESSION['schedule_id'];
        $enrollees = $conn->query($sql);
        if($enrollees!=null && $enrollees->num_rows > 0){
            echo "
            <form action='classlist.php' method='POST'>
                <table class='table table-hover'>
                    <tr>
                        <td>Name</td>
                        <td>Email</td>
                        <td>Remove Student</td>
                    </tr>"; 
            while($enrolled = $enrollees->fetch_assoc()){
                $sql = "SELECT * FROM users WHERE id=".$enrolled['user_id'];
                $student = ($conn->query($sql))->fetch_assoc();
                $student_id = $student['id'];
                echo "
                    <tr>
                        <td>".$student['firstname']." ".$student['lastname']."</td>
                        <td>".$student['email']."</td>
                        <td><button type='submit' name='remove' class='btn btn-outline-danger' value='$student_id'>Remove</button></td>
                    </tr>
                ";
            }
            echo "
                </table>
            </form>
            ";
        } else {
            echo "<label>No students enrolled!</label><br>";
        }
        // select students from users
        if($num_stud < $details['max_pop']){
            $eligible_students = [];
            $sql = "SELECT * FROM users WHERE type=2";
            $student_list = $conn->query($sql);
            if($student_list!=null){
                while($student = $student_list->fetch_assoc()){
                    $student['eligible'] = true; // select subjects student has enrolled
                    $sql = "SELECT * FROM subjects_enrolled WHERE user_id=".$student['id'];
                    $subjects_enrolled = $conn->query($sql);
                    if($subjects_enrolled!=null && $subjects_enrolled->num_rows > 0){
                        while($subject = $subjects_enrolled->fetch_assoc()){ // select schedules from schedule_details
                            $subject_schedules = [];
                            $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$subject['schedule_id'];
                            $schedule_list = $conn->query($sql); // schedules enrolled by the student
                            $sql = "SELECT * FROM schedules_details WHERE schedule_id=$schedule_id";
                            $master_sched = $conn->query($sql); // schedule of the subject
                            while($req_schedule = $master_sched->fetch_assoc()){
                                array_push($subject_schedules, $req_schedule);
                            }
                            while(($schedule = $schedule_list->fetch_assoc()) && $student['eligible'] == true){
                                for($count=0; $count<sizeof($subject_schedules) && $student['eligible'] == true; $count++){
                                    if($schedule['schedule_id'] == $subject_schedules[$count]['schedule_id']){
                                        $student['eligible'] = false;
                                    } else {
                                        if($schedule['timeday'] == $subject_schedules[$count]['timeday']){
                                            $timein = strtotime($schedule['timein']);
                                            $timeout = strtotime($schedule['timeout']);
                                            $req_timein = strtotime($subject_schedules[$count]['timein']);
                                            $req_timeout = strtotime($subject_schedules[$count]['timeout']);
                                            if($timein >= $req_timein && $timein < $req_timeout){
                                                $student['eligible'] = false;
                                            }
                                            if($timeout > $req_timein && $timeout <= $req_timeout){
                                                $student['eligible'] = false;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if($student['eligible'] == true){
                        array_push($eligible_students, $student);
                    }
                }
                if(sizeof($eligible_students) > 0){
                    echo "<br><br>
                        <form action='classlist.php' method='POST'>
                            <label>Students that can enroll: </label>
                            <select name='to_enroll' class='custom-select' style='width: 30%;'>";
                    for($count=0; $count<sizeof($eligible_students); $count++){
                        $student_id = $eligible_students[$count]['id'];
                        $student_name = $eligible_students[$count]['firstname']." ".$eligible_students[$count]['lastname'];
                        echo "<option value='$student_id'>$student_name</option>";
                    }
                    echo "
                            </select>
                            <button type='submit' name='submit' class='btn btn-outline-success'>Enroll</button>
                        </form>
                    ";

                    if(isset($_POST['submit'])){
                        $subject_id = $subject_details['id'];
                        $user_id = $_POST['to_enroll'];
                        $sql = "INSERT INTO subjects_enrolled(user_id, subject_id, schedule_id) VALUES('$user_id', '$subject_id', '$schedule_id')";
                        $conn->query($sql);
                        header("Location: classlist.php");
                    }
                } else {
                    echo "<br><label>No students to enroll!</label>";
                }
            } else {
                echo "<br><label>No students to enroll!</label>";
            }
        } else {
            echo "<br><label>Class list is full!</label>";
        }
        
        if(isset($_POST['remove'])){
            $sql = "DELETE FROM subjects_enrolled WHERE user_id=".$_POST['remove']." AND schedule_id=".$_SESSION['schedule_id'];
            $conn->query($sql);
            header("Location: classlist.php");
        }
        
        echo "<br><br><a href='admin.php'>Return to admin page?</a>";
    ?>
    </div>
    </div>
        

</body>
</html>