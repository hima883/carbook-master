<?php
session_start();

if(isset($_SESSION['owner_id'])){

    header("Location: OwnerCar/OwnerProfile.php");
    exit();

}

if(isset($_SESSION['tenant_license'])){

    header("Location: index.php");
    exit();

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Choose Role</title>

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

.container{

    width:900px;

    display:flex;

    justify-content:center;

    gap:40px;

}

.card{

    width:350px;

    background:white;

    border-radius:15px;

    padding:40px;

    text-align:center;

    box-shadow:0 5px 15px rgba(0,0,0,.15);

    transition:.3s;

}

.card:hover{

    transform:translateY(-8px);

}

.card h2{

    margin-bottom:15px;

    color:#333;

}

.card p{

    margin-bottom:30px;

    color:#666;

    line-height:1.6;

}

.btn{

    display:block;

    text-decoration:none;

    background:#0d6efd;

    color:white;

    padding:14px;

    border-radius:8px;

    margin-bottom:15px;

    transition:.3s;

}

.btn:hover{

    background:#0b5ed7;

}

.register{

    background:#198754;

}

.register:hover{

    background:#157347;

}

</style>

</head>

<body>

<div class="container">

    <div class="card">

        <h2>

            Car Owner

        </h2>

        <p>

            Login or create an Owner account to manage your cars and bookings.

        </p>

        <a

        href="OwnerCar/auth/OwnerLogin.php"

        class="btn">

            Login

        </a>

        <a

        href="OwnerCar/auth/OwnerRegister.php"

        class="btn register">

            Register

        </a>

    </div>



    <div class="card">

        <h2>

            Tenant

        </h2>

        <p>

            Login or create a Tenant account to browse and rent available cars.

        </p>

        <a

        href="tenant/auth/TenantLogin.php"

        class="btn">

            Login

        </a>

        <a

        href="tenant/auth/TenantRegister.php"

        class="btn register">

            Register

        </a>

    </div>

</div>

</body>

</html>