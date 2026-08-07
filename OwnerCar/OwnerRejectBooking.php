<?php

require_once '../mysql/db_connect.php';

session_start();

if(!isset($_SESSION['owner_id'])){

    header("Location: auth/OwnerLogin.php");
    exit();

}

// =======================================
// Owner ID
// =======================================

$owner_id = $_SESSION['owner_id'];


// =======================================
// Check Booking ID
// =======================================

if(!isset($_GET['booking_id'])){

    die("Booking ID Not Found");

}


$booking_id = $_GET['booking_id'];




// =======================================
// Get Booking Information
// =======================================

$get_booking = "

SELECT

bookings.id,

bookings.booking_status,

cars.owner_id

FROM bookings

INNER JOIN cars

ON bookings.car_id = cars.id

WHERE

bookings.id = ?

";


$result = $conn->execute_query($get_booking,[

    $booking_id

]);



if($result->num_rows == 0){

    die("Booking Not Found");

}



$booking = $result->fetch_assoc();




// =======================================
// Check Owner Permission
// =======================================


if($booking['owner_id'] != $owner_id){

    die("You are not allowed to reject this booking");

}


// =======================================
// Reject Booking
// =======================================


$reject_booking = "

UPDATE bookings

SET booking_status = 'cancelled'

WHERE id = ?

";



$conn->execute_query($reject_booking,[

    $booking_id

]);




// =======================================
// Success Message
// =======================================


echo "

<script>

alert('Booking rejected successfully.');

window.location='OwnerBookingRequests.php';

</script>

";


?>