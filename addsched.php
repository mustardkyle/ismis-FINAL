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
        $_SESSION['schedule_id'];
        if(!isset($_SESSION['user']) || $_SESSION['user']['type'] != 0){
            $_SESSION['error'] = 'Invalid Session!';
            header("Location: login.php");
        }

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ismis";
        $editted = null;

        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "SELECT * FROM subjects WHERE id=".$_SESSION['subject_id'];
        $subject_name = (($conn->query($sql))->fetch_assoc())['name'];
        if($_SESSION['error'] == 'Invalid credentials.' || $_SESSION['error'] == 'Invalid Session!'){
            $_SESSION['error'] = '';
        }
    ?>
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
    <div class="card border-primary mb-3" style="margin: auto; width: 50%; margin-top: 30px">
        <div class='card-head'>
            <?php
                if($_SESSION['error']!=null){
                    echo "<label class='text-danger' style='padding-top:20px; padding-left: 20px;'>".$_SESSION['error']."<label>";
                }
            ?>
        </div>
        <div class="card-body">
            <?php  if(!isset($_POST['submitsched'])){?>
            <div class="form-group">
                <form action="addsched.php" method="POST">
                    <label>Schedule for <?php echo $subject_name?></label>
                    <table class="table table-hover">
                        <tr>
                            <td>Time in</td>
                            <td>Time out</td>
                            <td>Day</td>
                        </tr>
                        <?php
                            // check if time in and time out collide
                            function daySelector($day, $timeday){
                                return ($timeday != null && $day == $timeday) ?'selected':'';
                            }
                            $count;
                            if(isset($_SESSION['edit'])){
                                $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$_SESSION['schedule_id'];
                                $result = $conn->query($sql);
                                $sql = "SELECT * FROM subjects_schedules WHERE schedule_id=".$_SESSION['schedule_id'];
                                $subject_details = ($conn->query($sql))->fetch_assoc();
                                $sql = "SELECT * FROM users WHERE id=".$subject_details['professor_id'];
                                $professor_edit = ($conn->query($sql))->fetch_assoc();
                                $max_pop = $subject_details['max_pop'];
                                $_SESSION['max_pop'] = $max_pop;
                                $_SESSION['professor_id'] = $subject_details['professor_id'];
                            }
                            for($count = 0; $count<$_SESSION['days']; $count++){
                                if(isset($_SESSION['edit'])){
                                    $row = $result->fetch_assoc();
                                    $timein = strtotime($row['timein']);
                                    $timeout = strtotime($row['timeout']);
                                    $timeday = $row['timeday'];
                                } else {
                                    $timeday = $timeout = $timein = null;
                                }
                                echo '
                                    <tr>
                                    <td>
                                        <select name="schedule['.$count.'][timein]" class="custom-select">';
                                    for($hour=07, $min=30; $hour!=20;){
                                        $value = (($hour<10)?"0$hour":"$hour")."$min"."00";
                                        echo "<option value='$value' ".daySelector(strtotime($value), $timein).">$hour:".$min."</option>";
                                        if($min==30){
                                            $min='00';
                                            $hour++;
                                        } else {
                                            $min+=30;
                                        }
                                    }
                                echo '
                                    </select>
                                    </td>
                                    <td><select name="schedule['.$count.'][timeout]" class="custom-select">';
                                for($hour=07, $min=30; $hour!=20;){
                                    $value = (($hour<10)?"0$hour":"$hour")."$min"."00";
                                    echo "<option value='$value' ".daySelector(strtotime($value), $timeout).">$hour:".$min."</option>";
                                    if($min==30){
                                        $min='00';
                                        $hour++;
                                    } else {
                                        $min+=30;
                                    }
                                }
                                echo '</select></td>
                                        <td>
                                            <select name="schedule['.$count.'][timeday]" class="custom-select">
                                                <option value="M"'.daySelector('M', $timeday).'>Monday</option>
                                                <option value="T"'.daySelector('T', $timeday).'>Tuesday</option>
                                                <option value="W"'.daySelector('W', $timeday).'>Wednesday</option>
                                                <option value="Th"'.daySelector('Th', $timeday).'>Thursday</option>
                                                <option value="F"'.daySelector('F', $timeday).'>Friday</option>
                                            </select>
                                        </td>
                                    </tr>
                                ';
                            }
                        ?>
                    </table>
                    <button type='submit' name='submitsched' class="btn btn-outline-success">Submit</button>
                </form>
            </div>
            <?php
                }
                if(isset($_POST['submitsched'])){// selects faculty
                    $validsched = true;
                    if(isset($_SESSION['edit'])){
                        $_SESSION['edit'] = null;
                        $editted = 1;
                    }
                    for($x = 0; $x < $_SESSION['days'] && $validsched == true; $x++){
                        if($_POST['schedule'][$x]['timein'] >= $_POST['schedule'][$x]['timeout']){
                            $validsched = false;
                        }
                        for($y=$x+1; $y < $_SESSION['days'] && $validsched == true; $y++){
                            if($_POST['schedule'][$x]['timeday'] == $_POST['schedule'][$y]['timeday']){
                                $timein = strtotime($_POST['schedule'][$x]['timein']);
                                $timeout = strtotime($_POST['schedule'][$x]['timeout']);
                                $sched_timein = strtotime($_POST['schedule'][$y]['timein']);
                                $sched_timeout = strtotime($_POST['schedule'][$y]['timeout']);
                                if($timein >= $sched_timein && $timein < $sched_timeout){
                                    $validsched = false;

                                }
                                if($timeout > $sched_timein && $timeout <= $sched_timeout){
                                    $validsched = false;
                                }
                            }
                        }
                    }
                    if($validsched == false){
                        $_SESSION['error'] = 'Invalid schedule';
                        if($editted == 1){
                            $_SESSION['edit'] = 1;
                        }
                    ?>
                        <script type="text/javascript">window.location.href = 'addsched.php';</script>
                    <?php    
                    } else {
                        $_SESSION['error'] = '';
                        $eligible_faculty = [];
                        $sql = "SELECT * FROM users WHERE type=1";
                        $faculty = $conn->query($sql);
                        if($faculty!=null && $faculty->num_rows > 0){
                            while($teacher = $faculty->fetch_assoc()){  //selects faculty schedules
                                $teacher['eligible'] = true;
                                $sql = "SELECT * FROM subjects_schedules WHERE professor_id=".$teacher['id'];
                                $result = $conn->query($sql);
                                if($result!=null && $result->num_rows > 0){ // selects schedules in schedules_details
                                    while($schedule_id = $result->fetch_assoc()){
                                        // echo $teacher['firstname'];
                                        $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$schedule_id['schedule_id'];
                                        $schedule_list = $conn->query($sql);
                                        if($schedule_list!=null && $schedule_list->num_rows > 0){
                                            while($schedule = $schedule_list->fetch_assoc()){
                                                for($x=0; $x<$_SESSION['days'] && $teacher['eligible']==true; $x++){
                                                    if($_POST['schedule'][$x]['timeday'] == $schedule['timeday']){
                                                        $timein = strtotime($_POST['schedule'][$x]['timein']);
                                                        $timeout = strtotime($_POST['schedule'][$x]['timeout']);
                                                        $sched_timein = strtotime($schedule['timein']);
                                                        $sched_timeout = strtotime($schedule['timeout']);
                                                        if($timein >= $sched_timein && $timein < $sched_timeout ) {
                                                            $teacher['eligible'] = false;
                                                        }
                                                        if($timeout > $sched_timein && $timeout <= $sched_timeout ){
                                                            $teacher['eligible'] = false;
                                                        }   
                                                    } 
                                                }
                                            }
                                        } // else available??
                                    }
                                }
                                if($teacher['eligible'] == true){
                                    array_push($eligible_faculty, $teacher);
                                }
                            }
                        } else {
                            // ERROR MESSAGE DON'T FORGET TO ADD CSS
                            echo "<label>No faculty has been registered or </label>";
                        }
                    }
                    if(isset($editted)){
                        $sql = "SELECT * FROM users WHERE id=".$_SESSION['professor_id'];
                        $professor_edit = ($conn->query($sql))->fetch_assoc();
                        for($count=0; $count<sizeof($eligible_faculty) && $eligible_faculty[$count]['id'] != $professor_edit['id']; $count++){}
                        if($count == sizeof($eligible_faculty)){
                            array_push($eligible_faculty, $professor_edit);
                        }
                    }
                    // adding schedules
                    function profSelector($id, $prof_id){
                        return ($prof_id!=null && $prof_id == $id)? 'selected': '';
                    }
                    if(isset($eligible_faculty) && count($eligible_faculty) > 0){
                        $count;
                        if(isset($editted)){
                            $prof_id = $professor_edit['id'];
                            $max_pop = $_SESSION['max_pop'];
                        } else {
                            $max_pop = 0;
                            $prof_id = null;
                            $editted = 0;
                        }
                        echo "
                            <form action='addsched.php' method='POST'>
                                <label>How many students can enroll?</label><br>
                                <input type='number' name='max_pop' value='$max_pop' class='input-group mb-3'><br>
                                <label>Faculty available to teach: </label>
                                <select name='faculty_available' class='custom-select'>
                                ";
                        for($count = 0; $count<count($eligible_faculty); $count++){
                            $id = $eligible_faculty[$count]['id'];
                            $name = $eligible_faculty[$count]['firstname']." ".$eligible_faculty[$count]['lastname'];
                            echo "<option value='$id' ".profSelector($id, $prof_id).">$name</option>";
                        }
                        echo"</select>";
                        for($count=0; $count<$_SESSION['days']; $count++){
                            $timein = $_POST['schedule'][$count]['timein'];
                            $timeout = $_POST['schedule'][$count]['timeout'];
                            $timeday = $_POST['schedule'][$count]['timeday'];
                            echo "<input type='hidden' value='$timein'  name='schedule[$count][timein]'>";
                            echo "<input type='hidden' value='$timeout' name='schedule[$count][timeout]'>";
                            echo "<input type='hidden' value='$timeday' name='schedule[$count][timeday]'>";
                        }
                        echo"
                            <button type='submit' name='submit' value='$editted' class='btn btn-outline-primary'>Submit Schedule</button>
                            </form>
                        ";
                    } else {
                        echo "<label>No faculty is eligible for the following schedules</label><br>
                            <a href='addsched.php'>return to the previous page?</a>
                        ";
                    }
                }
                if(isset($_POST['submit'])){
                    // insert subjects_schedules
                    $sql = "SELECT * FROM subjects_enrolled WHERE schedule_id=".$_SESSION['schedule_id'];
                    $num = $conn->query($sql);
                    if(($num!=null && $num->num_rows > $_POST['max_pop']) || $_POST['max_pop'] <= 0){
                        $_SESSION['error'] = 'Max Population error';
                        if($_POST['submit']==1){
                            $_SESSION['edit'] = 1;
                        }
                    ?>
                    <script type="text/javascript">window.location.href = 'addsched.php';</script>
                    <?php 
                    } else {
                        $subject_id = $_SESSION['subject_id'];
                        $max_pop = $_POST['max_pop'];
                        $professor_id = $_POST['faculty_available'];
                        if($_POST['submit'] == 1){  
                            $sql = "UPDATE subjects_schedules SET max_pop = $max_pop, professor_id = $professor_id WHERE schedule_id=".$_SESSION['schedule_id'];
                        } else {
                            $sql = "INSERT INTO subjects_schedules(subject_id, max_pop, professor_id) VALUES('$subject_id', '$max_pop', '$professor_id')";
                        }
                        $conn->query($sql);
                        // insert schedules_details
                        if($_POST['submit'] == 1){
                            $schedule_id = $_SESSION['schedule_id'];
                            $sql = "SELECT * FROM schedules_details WHERE schedule_id=$schedule_id";
                            $result = $conn->query($sql);
                        } else {
                            $sql = "SELECT schedule_id FROM subjects_schedules ORDER BY schedule_id DESC LIMIT 1";
                            $result = ($conn->query($sql))->fetch_assoc();
                            $schedule_id = $result['schedule_id'];
                        }
                        for($count = 0; $count<$_SESSION['days']; $count++){
                            $timein = $_POST['schedule'][$count]['timein'];
                            $timeout = $_POST['schedule'][$count]['timeout'];
                            $timeday = $_POST['schedule'][$count]['timeday'];
                            if($_POST['submit'] == 1){
                                $row = $result->fetch_assoc();
                                $row_timein = $row['timein'];
                                $row_timeout = $row['timeout'];
                                $row_timeday = $row['timeday'];
                                $sql = "UPDATE schedules_details 
                                        SET timein = '$timein', timeout='$timeout', timeday='$timeday'
                                        WHERE schedule_id = '$schedule_id' AND timein='$row_timein' AND timeout='$row_timeout'
                                            AND timeday = '$row_timeday'
                                        ";
                            } else {
                                $sql = "INSERT INTO schedules_details(schedule_id, timein, timeout, timeday) 
                                        VALUES('$schedule_id', '$timein', '$timeout', '$timeday')";
                            }
                            $conn->query($sql);
                            $_SESSION['error'] = '';
                            ?><script type="text/javascript">window.location.href = 'admin.php';</script><?php
                        }
                    }
                }
            ?>
        </div>
    </div>
</body>
</html>