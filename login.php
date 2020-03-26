<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<style>
    body {
        padding: 0;
        margin: 0;
        background: url('pics/bg.jpg');
        background-size: cover;
        background-repeat: no-repeat;
    }
    .container {
        width: 370px;
        height: 370px;
        background-color: #ffffff;
        border-radius: 3px;
        padding-top: 40px;
        background: url('pics/bg.jpg');
        border-radius: 15px ;
        opacity: 1;
    }
    .header{
        padding-top: 80px;
    }
    .container input {
        border-radius: 10px;
        border: 1px;
    }
     .form-group {
        border-raidus: 1px;
    } */

</style>

<body>
    <?php
        session_start();

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ismis";

        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        if(!isset($_SESSION['error'])){
            $_SESSION['error'] = '';
        }

        if(isset($_POST['submit'])){
            if($_POST['email'] != '' && $_POST['password'] != ''){
                $email = $_POST['email'];
                $password = $_POST['password'];
                $sql = "SELECT * FROM users WHERE email = '$email'";
                $result = $conn->query($sql);
                if($result!=null){
                    $row = $result->fetch_assoc();
                    if($row!=null && password_verify($password, $row['password'])){
                        $_SESSION['user'] = $row;
                        switch($row['type']){
                            case 0: header("Location: admin.php"); break;
                            case 1: header("Location: faculty.php"); break;
                            case 2: header("Location: student.php"); break;
                        }
                    } else {
                        $_SESSION['error'] = "Invalid credentials.";
                    }
                } else {
                    $_SESSION['error'] = "Invalid credentials.";
                }
            } else {
                $_SESSION['error'] = "Invalid credentials.";
            }
        }
    ?>

    <div style="position: absolute; left:50%; transform:translate(-50%);">
      <div class="header">
          <center ><img src="pics/reportheader2.png" style="height:78px; width: 600px;"> </center>
          <i class="fa-shield-alt"></i>
      </div>
      <br><br>
      <div class="container">
          <form action="login.php" method="post">
              <div class="form-group">
              <center>
              <h3> Login to your account</h3><hr style="border: 1px solid white;">
              <?php echo "<label class='text-danger'>".$_SESSION['error']."<label> <br>"; ?>
              <br>
              <input type="text" name="email" placeholder="Username" style="width: 295px; height: 30px; padding-left: 10px;"><br><br>
              <input type="password" name="password" placeholder="Password" style="width: 295px; height: 30px; padding-left: 10px;  "><br><br>

              <button type="submit" class="btn btn-success" name="submit">Submit</button><br><br><br>
              </center>
              </div>
          </form>
          <center> <label>No account? <a href="register.php">Register</a></label> </center>
      </div>
    </div>
</body>
</html>