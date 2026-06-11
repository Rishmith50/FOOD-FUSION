<?php
include "db.php";

if (isset($_POST['register'])) {

    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (first_name, last_name, email, password_hash)
            VALUES ('$fname','$lname','$email','$password')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Registration Successful'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('Email already exists');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Register</title>
<style>
body { font-family: Arial; 
background:#4b5cbf; 
color:white; 
text-align:center; 
padding-top:100px; }

form { background:white; color:black; width:300px; margin:auto; padding:40px; border-radius:20px; }
input { width:100%; padding:8px; margin:8px 0; }
button { padding:8px 15px; background:#4b5cbf; color:white; border:none; }
a { color:white; }
</style>
</head>
<body>

<h2>Create Account</h2>

<form method="POST">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="last_name" placeholder="Last Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="register">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login</a></p>

</body>
</html>