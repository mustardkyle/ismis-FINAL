<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">


</head>
<style>
    body {
        padding: 0;
        margin: 0;
        background: url('/ismis/pics/bg.jpg');
        background-size: cover;
        background-repeat: no-repeat;
    }
    .container {
        width: 370px;
        background-color: #ffffff;
        border-radius: 3px;
        padding-top: 20px;
        background: url('/ismis/pics/bg.jpg');
        border-radius: 15px ;
        opacity: 1;
    }
    .header{
        padding-top: 70px;
    }
    .container input {
        border-radius: 10px;
        border: 1px;
    }
     .form-group {
        border-raidus: 1px;
    }
    .viewmode {
        position: absolute; 
        left:50%; 
        transform:translate(-50%);
        /* overflow-y:scroll; 
        height:100%; */
    }
</style>

<body>
    <?php
        session_start();
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ismis";
        $error = "";
        $_SESSION['error'] = '';

        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        
        if(isset($_POST['submit'])){
            if($_POST['firstname'] == null || $_POST['lastname'] == null || $_POST['email'] == null || $_POST['password'] == null ||$_POST['confirmpassword'] == null){
                $error = 'Some fields are empty!';
            } else if($_POST['password'] === $_POST['confirmpassword']){
                $email = $_POST['email'];
                $sql = "SELECT * FROM users WHERE email = '$email'";
                $result = $conn->query($sql);
                if($result->num_rows > 0){
                    $error = "Email is already registered!";
                } else {
                    $firstname = $_POST['firstname'];
                    $lastname = $_POST['lastname'];
                    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                    $type = $_POST['type'];

                    $sql = "insert into users(firstname, lastname, email, password, type)
                    values('$firstname', '$lastname', '$email', '$password', '$type')";
                    $conn->query($sql);
                    header("Location: login.php");
                }
            } else { // refresh and display error also check if email already exists
                $error = "Passwords don't match!";
            }
        }
        ?>
        <div class="viewmode">
            <div class="header">
                <center ><img src="/ismis/pics/reportheader2.png" style="height:78px; width: 600px;"> </center>
                <i class="fa-shield-alt"></i>
            </div>
            <br><br>
            <div class="container">
                <form action="register.php" method="post">
                    <div class="form-group">
                        <center>
                            <h3> Create your account</h3><hr style="border: 1px solid white;">
                            <br>
                            <?php if($error!=""){ echo "<label class='text-danger'>$error</label><br>"; }?>
                            <input type="text" name="firstname" placeholder="First name" style="width: 295px; height: 30px; padding-left: 10px;"><br><br>
                            <input type="text" name="lastname" placeholder="Last name" style="width: 295px; height: 30px; padding-left: 10px;"><br><br>
                            <input type="email" name="email" placeholder="Email" style="width: 295px; height: 30px; padding-left: 10px;"><br><br>
                            <input type="password" name="password" placeholder="Password" style="width: 295px; height: 30px; padding-left: 10px;"><br><br>
                            <input type="password" name="confirmpassword" placeholder="Confirm Password" style="width: 295px; height: 30px; padding-left: 10px;"><br><br>
                            <select name="type">
                                <option value="1">Faculty</option>
                                <option value="2">Student</option>
                            </select><br><br>
                            <button type="submit" class="btn btn-success" name="submit">Submit</button><br>
                        </center>
                    </div>
                </form>
                <center> <label>Already registered? <a href="login.php">Login</a></label> </center>
            </div>
        </div>
    
</body>
</html>