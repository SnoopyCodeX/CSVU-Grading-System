<?php
session_start();
require ("../../configuration/config.php");
require '../../auth/controller/auth.controller.php';

if (!AuthController::isAuthenticated()) {
    header("Location: ../../public/login");
    exit();
}

if(isset($_POST['addUser'])){
    $fname = $dbCon->real_escape_string($_POST['firstname']);
    $mname = $dbCon->real_escape_string($_POST['middlename']);
    $lname = $dbCon->real_escape_string($_POST['lastname']);
    $gender = $dbCon->real_escape_string($_POST['gender']);
    $sid = $dbCon->real_escape_string($_POST['student_id']);
    $email = filter_var($dbCon->real_escape_string($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $dbCon->real_escape_string($_POST['password']);
    $role = $dbCon->real_escape_string($_POST['role']);
    $contact = $dbCon->real_escape_string($_POST['contact']);

    $pwd = crypt($password,'$6$Crypt$');

    $emailExists = $dbCon->query("SELECT * FROM ap_userdetails WHERE email = '$email'");
    $studentIdExists = $dbCon->query("SELECT * FROM ap_userdetails WHERE sid = '$sid'");

    if ($emailExists->num_rows > 0) {
        echo "Error: Email already exists";
    } elseif ($studentIdExists->num_rows > 0) {
        echo "Error: Student ID already exists";
    } else {

        $sql = "INSERT INTO ap_userdetails (firstName, middleName, lastName, gender, sid, email, password, roles, contact)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        
        $stmt = $dbCon->prepare($sql);

        $stmt->bind_param("sssssssss", $fname, $mname, $lname, $gender, $sid, $email, $pwd, $role, $contact);

        if ($stmt->execute()) {
            echo "Data inserted successfully";
        } else {
            echo "Error: " . $stmt->error;
        }

        
        $stmt->close();
    }

    
    $dbCon->close();
}
        
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aligned Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        form {
            max-width: 400px;
            margin: 0 auto;
        }

        div {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <form action="" method="post">
        <div>
            <label for="firstname">Firstname</label>
            <input type="text" name="firstname" id="firstname" placeholder="Enter your firstname" required>
        </div>
        <div>
            <label for="middlename">Middlename</label>
            <input type="text" name="middlename" id="middlename" placeholder="Enter your middlename" required>
        </div>
        <div>
            <label for="lastname">LastName</label>
            <input type="text" name="lastname" id="lastname" placeholder="Enter your lastname" required>
        </div>
        <div>
            <label for="contact">Contact Number</label>
            <input type="text" name="contact" id="contact" placeholder="Contact Number" required>
        </div>
        <div>
            <label for="gender">Gender</label>
            <select name="gender" id="gender" required>
                <option value="" selected disabled>Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>
        <div id="studentIdField">
            <label for="student_id">Student ID</label>
            <input type="text" name="student_id" id="student_id" placeholder="student ID" required>
        </div>
        <div>
            <label for="user1">Email</label>
            <input type="email" name="email" id="email" placeholder="Email" required>
        </div>
        <div>
            <label for="user2">Password</label>
            <input type="password" name="password" id="password" placeholder="Password" required>
        </div>
        <div >
            <label for="userN" >Roles</label>
            <select name="role" id="userRole" required>
                <option value="user">Student</option>
                <option value="instructor">Instructor</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" name="addUser">Submit</button>
    </form>
</body>
<script>
    document.getElementById('userRole').addEventListener('change', function() {
        var selectedRole = this.value;
        var studentIdField = document.getElementById('studentIdField');
        if (selectedRole === 'user') {
            studentIdField.style.display = 'block';
        } else {
            studentIdField.style.display = 'none';
        }
    });
</script>
</html>
