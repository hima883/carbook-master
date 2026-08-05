<?php

require_once '../mysql/db_connect.php';


// =======================================
// Owner ID
// =======================================

$owner_id = 1;

// Replace with $_SESSION['owner_id'] after creating Owner Login Session



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

bookings.*,

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




// Check Owner Permission

if($booking['owner_id'] != $owner_id){

    die("You are not allowed to approve this booking");

}



// =======================================
// Approve Booking
// =======================================


// Update Booking Status

$update_booking = "

UPDATE bookings

SET booking_status = 'approved'

WHERE id = ?

";


$conn->execute_query($update_booking,[

    $booking_id

]);




// Update Car Status

$update_car = "

UPDATE cars

SET status = 'rented'

WHERE id = ?

";


$conn->execute_query($update_car,[

    $booking['car_id']

]);





// =======================================
// Create Payment Record
// =======================================


$insert_payment = "

INSERT INTO payments

(

    booking_id,

    amount,

    payment_status

)

VALUES

(

    ?,

    ?,

    'pending'

)

";


$conn->execute_query($insert_payment,[

    $booking_id,

    $booking['total_price']

]);




// =======================================
// Cancel Conflicting Pending Requests
// =======================================


$cancel_requests = "

UPDATE bookings

SET booking_status = 'cancelled'

WHERE

car_id = ?

AND

id != ?

AND

booking_status = 'pending'

AND

(

    pickup_datetime <= ?

    AND

    return_datetime >= ?

)

";


$conn->execute_query($cancel_requests,[

    $booking['car_id'],

    $booking_id,

    $booking['return_datetime'],

    $booking['pickup_datetime']

]);




// =======================================
// Success Message
// =======================================


echo "

<script>

alert('Booking approved successfully.');

window.location='BookingRequests.php';

</script>

";


?>

