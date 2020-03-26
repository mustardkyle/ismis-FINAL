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
        // add subject doesn't work
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
    <div class="card border-primary mb-3" style="max-width: 70%; margin:auto; margin-top: 30px">
       <center> <div class="card-header">Admin Control Panel</div> </center>
        <div class="card-body">
        <br><h4 class='card-title'>Subjects offered:</h4><br> 
    <?php
        $sql = "select * from subjects";
        $result = $conn->query($sql);
        // check if subejct list is empty
        if ($result!=null && $result->num_rows > 0) { // for each subject
            echo "
            <table class='table table-hover' style='width: 60%;'>
            <form action ='admin.php' method='POST' >
                <thead>
                <tr>
                    <th>Name</th>
                    <th align='center'># of groups</th>
                    <th></th>
                </tr>
                </thead>
            ";
            while($row = $result->fetch_assoc()) { // gets the schedules for each subject
                $sql = "select * from subjects_schedules where subject_id=".$row['id'];
                $num = $conn->query($sql);
                if($num!=null){
                    echo "
                        <tr >
                            <td><button name='display' value='".$row['id']."' class='btn btn-outline-primary'>".$row['name']."</button></td>
                            <td>".$num->num_rows."</td>
                            <td style='width: 40%;' align='right'><button name='deleteSubj' value='".$row['id']."' class='btn btn-outline-danger'>Delete</button>    <button name='editSubj' value='".$row['id']."' class='btn btn-outline-info'>Edit</button></td>
                        </tr>
                    ";
                }
            }
            echo "</form>
            </table>";
        } else {
            echo "No subjects found!";
        }
        // insert
        if(isset($_POST['editSubj'])){
            $sql = "SELECT * FROM subjects WHERE id=".$_POST['editSubj'];
            $result = $conn->query($sql);
            $subject_name = ($result->fetch_assoc())['name'];
            $subject_id = $_POST['editSubj'];
            $subject = "subjectEdit";
        } else {
            $subject_name = '';
            $subject_id = '';
            $subject = "subject";
        }
    ?><br>
    <form action="admin.php" method="POST">
        <div class="form-group">
        <label class="col-form-label" for="inputDefault">Subject name: </label>
            <input type="text" class="form-control" id="inputDefault" style="width: 30%;" name="subject_name" placeholder="Add a new subject!" value="<?php echo $subject_name ?>">
            <br><button class="btn btn-outline-success" type="submit" name="<?php echo $subject ?>">Submit</button><br><br>
            <input type="hidden" value="<?php echo $subject_id ?>" name="id">
        </div>
    </form>
    <!-- Forms for subjects and it's logic -->
    <br><br>
    <?php
        if(isset($_POST['subjectEdit'])){
            $sql = "UPDATE subjects SET name='".$_POST['subject_name']."' WHERE id=".$_POST['id'];
            $conn->query($sql);
            header("Location: admin.php");
        }

        if(isset($_POST['deleteSubj'])){
            $sql = "DELETE FROM subjects WHERE id=".$_POST['deleteSubj'];
            $conn->query($sql);
            header("Location: admin.php");
        }
        if(isset($_POST['subject'])){
            $subject = $_POST['subject_name'];
            $sql = "INSERT INTO subjects(name) VALUES('$subject')";
            $conn->query($sql);
            header("Location: admin.php");  
        }
    ?>
    <!-- Displays the schedules per subject plus forms (DO update and delete) -->
    <?php
        if(isset($_POST['display'])){ // Selects all subject details from the subject_id from button press
            $subject_id = $_POST['display'];
            $sql = "SELECT * FROM subjects_schedules WHERE subject_id=".$_POST['display'];
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            if($row!=null){
                echo "
                <form action='admin.php' method='POST'>
                    <table class='table table-hover'>
                        <tr>
                            <th>Schedule</th>
                            <th>Population</th>
                            <th>Teacher</th>
                            <th></th>
                        </tr>
                "; // use $row to access subject_schedule
                do{  // selects all subject schedules 
                    $schedule_id = $row['schedule_id'];
                    $sql = "SELECT * FROM schedules_details WHERE schedule_id=$schedule_id";
                    $schedule = $conn->query($sql); // gets number of students enrolled
                    $sql = "SELECT * FROM subjects_enrolled WHERE schedule_id=$schedule_id";
                    $num = $conn->query($sql); // gets professor name
                    $sql = "SELECT firstname, lastname FROM users WHERE id=".$row['professor_id'];
                    $professor = ($conn->query($sql))->fetch_assoc();
                    echo "
                        <tr>
                            <td>";
                    while($day = $schedule->fetch_assoc()) {
                        echo $day['timeday']." ".$day['timein']."-".$day['timeout']."<br>";
                    }
                    echo "</td>
                            <td>".$num->num_rows."/".$row['max_pop']."</td>
                            <td>".$professor['firstname']." ".$professor['lastname']."</td>
                            <td>
                                <button name='delete' type='submit' value='$schedule_id' class='btn btn-outline-danger'>Delete</button>
                                <button name='edit' type='submit' value='$schedule_id' class='btn btn-outline-info'>Edit Schedule</button>
                                <button type='submit' name='classlist' value='$schedule_id' class='btn btn-outline-primary'>Edit Class</button>
                            </td>
                        </tr>
                    ";
                } while($row = $result->fetch_assoc());
                echo "</form></table>";
            } else {
                echo "<label>No Schedules found!</label>";
            }
            echo "<br><br>
            <form method='POST' action='admin.php'>
                <input type='hidden' name='subject_id' value='$subject_id'>
                <label class='card-text'>How many schedules would you like to add? </label>
                <select name='days' class='custom-select' style='width: 10%'>
                    <option value='1'>1</option>
                    <option value='2'>2</option>
                    <option value='3'>3</option>
                    <option value='4'>4</option>
                    <option value='5'>5</option>
                </select>
                <button type='submit' name='addsched' class='btn btn-outline-primary'>Submit</button>
            </form>
            ";
        }
        if(isset($_POST['addsched'])){
            $_SESSION['days'] = $_POST['days'];
            $_SESSION['subject_id'] = $_POST['subject_id'];
            header("Location: addsched.php");
        }
        if(isset($_POST['classlist'])){
            $_SESSION['schedule_id'] = $_POST['classlist'];
            header("Location: classlist.php");
        }
        if(isset($_POST['delete'])){
            $sql = "DELETE FROM subjects_schedules WHERE schedule_id=".$_POST['delete'];
            $conn->query($sql);
            header("Location: admin.php");
        }
        if(isset($_POST['edit'])){
            $_SESSION['schedule_id'] = $_POST['edit'];
            $_SESSION['edit'] = 1;
            $sql = "SELECT * FROM subjects_schedules WHERE schedule_id=".$_POST['edit'];
            $result = ($conn->query($sql))->fetch_assoc();
            $_SESSION['subject_id'] = $result['subject_id'];
            $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$_POST['edit'];
            $subject_schedules = $conn->query($sql);
            $_SESSION['days'] = $subject_schedules->num_rows;
            header("Location: addsched.php");
        }
    ?>
            </div>
    </div>
</body>
</html>