<?php

require_once '../mysql/db_connect.php';

session_start();

if(!isset($_SESSION['tenant_license'])){

    header("Location: auth/TenantLogin.php");
    exit();

}

// =======================================
// Tenant License
// =======================================

$tenant_license = $_SESSION['tenant_license'];



// =======================================
// Get Tenant Information
// =======================================


$get_tenant = "

SELECT

name,

email,

phone,

driving_license,

damages_count

FROM tenants

WHERE driving_license = ?

";



$tenant_result = $conn->execute_query($get_tenant,[

    $tenant_license

]);



if($tenant_result->num_rows == 0){

    die("Tenant Not Found");

}



$tenant = $tenant_result->fetch_assoc();





// =======================================
// Booking Statistics
// =======================================


$get_booking_stats = "

SELECT

COUNT(*) AS total_bookings,

SUM(booking_status = 'pending') AS pending_bookings,

SUM(booking_status = 'approved') AS approved_bookings,

SUM(booking_status = 'cancelled') AS cancelled_bookings

FROM bookings

WHERE tenant_license = ?

";



$booking_result = $conn->execute_query($get_booking_stats,[

    $tenant_license

]);



$booking_stats = $booking_result->fetch_assoc();



// =======================================
// Payment Statistics
// =======================================


$get_payment_stats = "

SELECT

COUNT(*) AS total_payments,

SUM(amount) AS total_paid_amount

FROM payments

INNER JOIN bookings

ON payments.booking_id = bookings.id

WHERE

bookings.tenant_license = ?

AND

payments.payment_status = 'paid'

";



$payment_result = $conn->execute_query($get_payment_stats,[

    $tenant_license

]);



$payment_stats = $payment_result->fetch_assoc();





// =======================================
// HTML START
// =======================================

?>


<!DOCTYPE html>

<html>

<head>

<title>Tenant Profile</title>


<style>


body{

    font-family: Arial, sans-serif;

    background:#f4f6f8;

    margin:0;

    padding:30px;

}



.container{

    width:85%;

    margin:auto;

}



.card{

    background:white;

    padding:25px;

    margin-bottom:20px;

    border-radius:12px;

    box-shadow:0 4px 10px rgba(0,0,0,0.1);

}



h2{

    color:#333;

}



.info{

    display:flex;

    justify-content:space-between;

    flex-wrap:wrap;

}



.box{

    width:30%;

    min-width:200px;

    background:#fafafa;

    padding:20px;

    margin:10px;

    text-align:center;

    border-radius:10px;

}



.number{

    font-size:28px;

    font-weight:bold;

    color:#007bff;

}



a{

    text-decoration:none;

    background:#007bff;

    color:white;

    padding:12px 20px;

    border-radius:8px;

    display:inline-block;

    margin:5px;

}



</style>


</head>


<body>


<div class="container">



<!-- =======================================
     Tenant Information
======================================= -->


<div class="card">


<h2>Tenant Information</h2>



<p>

<strong>Name:</strong>

<?php echo $tenant['name']; ?>

</p>




<p>

<strong>Email:</strong>

<?php echo $tenant['email']; ?>

</p>




<p>

<strong>Phone:</strong>

<?php echo $tenant['phone']; ?>

</p>




<p>

<strong>Driving License:</strong>

<?php echo $tenant['driving_license']; ?>

</p>




<p>

<strong>Damages Count:</strong>

<?php echo $tenant['damages_count']; ?>

</p>


</div>







<!-- =======================================
     Booking Statistics
======================================= -->


<div class="card">


<h2>My Bookings</h2>



<div class="info">


<div class="box">

<h3>Total Bookings</h3>


<div class="number">

<?php echo $booking_stats['total_bookings']; ?>

</div>


</div>





<div class="box">

<h3>Pending</h3>


<div class="number">

<?php echo $booking_stats['pending_bookings']; ?>

</div>


</div>





<div class="box">

<h3>Approved</h3>


<div class="number">

<?php echo $booking_stats['approved_bookings']; ?>

</div>


</div>





<div class="box">

<h3>Cancelled</h3>


<div class="number">

<?php echo $booking_stats['cancelled_bookings']; ?>

</div>


</div>



</div>


</div>








<!-- =======================================
     Payment Statistics
======================================= -->


<div class="card">


<h2>Payments</h2>



<div class="info">



<div class="box">

<h3>Total Payments</h3>


<div class="number">

<?php echo $payment_stats['total_payments']; ?>

</div>


</div>





<div class="box">

<h3>Total Paid Amount</h3>


<div class="number">

<?php echo $payment_stats['total_paid_amount'] ?? 0; ?>

 EGP

</div>


</div>



</div>


</div>







<!-- =======================================
     Navigation Buttons
======================================= -->


<div class="card">


<a href="ShowCars.php">

Browse Cars

</a>



<a href="MyBookings.php">

My Bookings

</a>


</div>





</div>


</body>


</html>