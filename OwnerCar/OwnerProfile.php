<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');

// =======================================
// Logged-in User
// =======================================

$user_id = $_SESSION['user_id'];
$owner_id = $user_id;


// =======================================
// Make Sure User Is An Owner
// =======================================

$check_owner = $conn->prepare("
    SELECT user_id
    FROM owners
    WHERE user_id = ?
");

$check_owner->bind_param("i", $user_id);
$check_owner->execute();

$owner_result = $check_owner->get_result();

if ($owner_result->num_rows === 0) {
    die("You must be an owner to view this page.");
}

$check_owner->close();



// =======================================
// Get Owner Information
// =======================================


$get_owner = "
SELECT
    users.name,
    users.email,
    users.phone,
    owners.balance

FROM owners

INNER JOIN users
    ON owners.user_id = users.id

WHERE owners.user_id = ?
";

$owner_result = $conn->execute_query($get_owner, [
    $owner_id
]);

if ($owner_result->num_rows === 0) {
    die("Owner Not Found");
}

$owner = $owner_result->fetch_assoc();




// =======================================
// Cars Statistics
// =======================================


$get_cars_stats = "

SELECT

COUNT(*) AS total_cars,

SUM(status = 'available') AS available_cars,

SUM(status = 'rented') AS rented_cars

FROM cars

WHERE owner_id = ?

";


$cars_result = $conn->execute_query($get_cars_stats,[

    $owner_id

]);


$cars_stats = $cars_result->fetch_assoc();


// =======================================
// Booking Statistics
// =======================================


$get_booking_stats = "

SELECT

SUM(booking_status = 'pending') AS pending_bookings,

SUM(booking_status = 'approved') AS approved_bookings,

SUM(booking_status = 'completed') AS completed_bookings,

SUM(booking_status = 'cancelled') AS cancelled_bookings,

COUNT(*) AS total_bookings

FROM bookings

INNER JOIN cars

ON bookings.car_id = cars.id

WHERE cars.owner_id = ?

";


$booking_result = $conn->execute_query($get_booking_stats,[

    $owner_id

]);


$booking_stats = $booking_result->fetch_assoc();





// =======================================
// HTML START
// =======================================

?>


<!DOCTYPE html>

<html>

<head>

<title>Owner Profile</title>


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
     Owner Information
======================================= -->


<div class="card">


<h2>Owner Information</h2>


<p>
<strong>Name:</strong>

<?php echo $owner['name']; ?>

</p>



<p>
<strong>Email:</strong>

<?php echo $owner['email']; ?>

</p>



<p>
<strong>Phone:</strong>

<?php echo $owner['phone']; ?>

</p>



<p>
<strong>Balance:</strong>

<?php echo $owner['balance']; ?> EGP

</p>


</div>





<!-- =======================================
     Cars Statistics
======================================= -->


<div class="card">


<h2>My Cars</h2>



<div class="info">


<div class="box">

<h3>Total Cars</h3>

<div class="number">

<?php echo $cars_stats['total_cars']; ?>

</div>

</div>




<div class="box">

<h3>Available Cars</h3>

<div class="number">

<?php echo $cars_stats['available_cars']; ?>

</div>

</div>




<div class="box">

<h3>Rented Cars</h3>

<div class="number">

<?php echo $cars_stats['rented_cars']; ?>

</div>

</div>



</div>


</div>





<!-- =======================================
     Booking Statistics
======================================= -->


<div class="card">


<h2>Bookings</h2>


<div class="info">


<div class="box">

<h3>Total Bookings</h3>

<div class="number">

<?php echo $booking_stats['total_bookings']; ?>

</div>

</div>



<div class="box">

<h3>Pending Requests</h3>

<div class="number">

<?php echo $booking_stats['pending_bookings']; ?>

</div>

</div>



<div class="box">

    <h3>Completed</h3>

    <div class="number">
        <?php echo $booking_stats['completed_bookings']; ?>
    </div>

</div>

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
     Navigation Buttons
======================================= -->


<div class="card">

<a href="ShowOwnerCars.php">

Manage My Cars

</a>


<a href="OwnerBookingRequests.php">

Booking Requests

</a>

</div>

</div>

</body>

</html>