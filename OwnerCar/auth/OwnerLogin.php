<?php

session_start();

require_once "../../mysql/db_connect.php";

if(isset($_SESSION['owner_id'])){

    header("Location: ../OwnerProfile.php");
    exit();

}

$error = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = trim($_POST['email']);

    $password = trim($_POST['password']);

    if(

        empty($email)

        ||

        empty($password)

    ){

        $error = "Please fill in all fields.";

    }

    else{

        $query = "

        SELECT *

        FROM owners

        WHERE email = ?

        LIMIT 1

        ";

        $owner = $conn->execute_query($query,[

            $email

        ]);

        if($owner->num_rows == 0){

            $error = "Invalid email or password.";

        }

        else{

            $row = $owner->fetch_assoc();

            if($row['password'] != $password){

                $error = "Invalid email or password.";

            }

            else{

                $_SESSION['owner_id'] = $row['id'];

                $_SESSION['owner_name'] = $row['name'];

                $_SESSION['owner_email'] = $row['email'];

                $_SESSION['owner_phone'] = $row['phone'];

                header("Location: ../OwnerProfile.php");

                exit();

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Owner Login</title>

<style>

*{

    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;

}

body{

    background:#f4f6f9;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;

}

.login-box{

    width:450px;
    background:white;
    padding:35px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.15);

}

.login-box h2{

    text-align:center;
    margin-bottom:25px;
    color:#333;

}

.error{

    background:#ffe5e5;
    color:#b30000;
    padding:12px;
    border-radius:8px;
    margin-bottom:20px;
    font-weight:bold;

}

label{

    display:block;
    margin-top:15px;
    margin-bottom:8px;
    font-weight:bold;

}

input{

    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:8px;
    outline:none;

}

input:focus{

    border-color:#0d6efd;

}


button{

    width:100%;
    margin-top:25px;
    padding:14px;
    border:none;
    border-radius:8px;
    background:#0d6efd;
    color:white;
    font-size:16px;
    cursor:pointer;
    transition:.3s;

}

button:hover{

    background:#0b5ed7;

}

.register-link{

    text-align:center;
    margin-top:20px;

}

.register-link a{

    color:#0d6efd;
    text-decoration:none;
    font-weight:bold;

}

.register-link a:hover{

    text-decoration:underline;

}

</style>

</head>

<body>

<div class="login-box">

    <h2>

        Owner Login

    </h2>

    <?php

    if(!empty($error)){

    ?>

        <div class="error">

            <?= $error ?>

        </div>

    <?php

    }

    ?>

    <form method="POST">

        <label>

            Email

        </label>

        <input
        type="email"
        name="email"
        value="<?= $_POST['email'] ?? '' ?>"
        required>

        <label>

            Password

        </label>

        <input
        type="password"
        name="password"
        required>

        <button type="submit">

            Login

        </button>

    </form>

    <div class="register-link">

        Don't have an account?

        <a href="OwnerRegister.php">

            Register

        </a>

    </div>

</div>

</body>

</html>