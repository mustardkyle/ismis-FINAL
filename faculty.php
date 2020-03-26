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
        if(!isset($_SESSION['user']) || $_SESSION['user']['type'] != 1){
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
    <div class="card border-primary mb-3" style='margin: auto; width: 70%; margin-top:30px'>
    <div class="card-body">
    <?php
        echo "<h4>Welcome, ".ucfirst($_SESSION['user']['firstname'])." ".ucfirst($_SESSION['user']['lastname'])."</h3><hr>";
        $sql = "SELECT * FROM subjects_schedules WHERE professor_id=".$_SESSION['user']['id'];
        $schedule_list = $conn->query($sql);
        if($schedule_list != null && $schedule_list->num_rows > 0){
            echo "<br><h5>List of classes: </h5>";
            echo "
            <form method='POST' action='faculty.php'>
                <table class='table table-hover'>
                <tr>
                    <td>Group #</td>
                    <td>Course id</td>
                    <td>Course name</td>
                    <td>Schedule</td>
                    <td>Population</td>
                    <td></td>
                </tr>
            ";
            
            while($schedule = $schedule_list->fetch_assoc()){
                $sql = "SELECT * FROM schedules_details WHERE schedule_id=".$schedule['schedule_id'];
                $result = $conn->query($sql);
                $sql = "SELECT * FROM subjects WHERE id=".$schedule['subject_id'];
                $subject = ($conn->query($sql))->fetch_assoc();
                $sql = "SELECT * FROM subjects_enrolled WHERE schedule_id=".$schedule['schedule_id'];
                $population = ($conn->query($sql))->num_rows;
                $sql = "SELECT * FROM subjects_schedules WHERE schedule_id=".$schedule['schedule_id'];
                $max_pop = (($conn->query($sql))->fetch_assoc())['max_pop'];
                echo "
                <tr>
                    <td>".$schedule['schedule_id']."</td>
                    <td>".$schedule['subject_id']."</td>
                    <td>".$subject['name']."</td>
                    <td>
                ";
                while($time = $result->fetch_assoc()){
                    echo $time['timeday']." ".$time['timein']."-".$time['timeout']."<br>";
                }
                echo "</td>
                    <td>$population/$max_pop</td>
                    <td><button type='submit' name='classlist' value='".$schedule['schedule_id']."' class='btn btn-outline-info'>Classlist</button></td>
                </tr>
                    ";
            }
            echo "   
                </table>
            </form>
            ";
        } else {
            echo "<label>No schedules asssigned!</label>";
        }

        if(isset($_POST['classlist'])){
            $sql = "SELECT * FROM subjects_enrolled where schedule_id=".$_POST['classlist'];
            $result = $conn->query($sql);
            if($result!=null && $result->num_rows){
                echo "<br><br>
                <h5>Classlist of Group #".$_POST['classlist']."</h5>
                    <table class='table table-hover'>
                        <tr>
                            <th>Id number</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>";
                while($subject_enrolled = $result->fetch_assoc()){
                    $sql = "SELECT * FROM users WHERE id=".$subject_enrolled['user_id'];
                    $student = ($conn->query($sql))->fetch_assoc();
                    echo "
                        <tr>
                            <td>".$student['id']."</td>
                            <td>".$student['firstname']." ".$student['lastname']."</td>
                            <td>".$student['email']."</td>
                        </tr>
                    ";
                }   
                echo "
                    </table>
                ";
            } else {
                echo "<label>No one enrolled!</label>";
            }
        }
    ?>
    </div>
    </div>
</body>
</html>