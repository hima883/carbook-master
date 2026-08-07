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
// Get Payment + Booking Information
// =======================================

$get_payment = "

SELECT

payments.id AS payment_id,

payments.amount,

payments.payment_status,

bookings.booking_status,

bookings.pickup_datetime,

bookings.car_id,

cars.owner_id

FROM payments

INNER JOIN bookings

ON payments.booking_id = bookings.id

INNER JOIN cars

ON bookings.car_id = cars.id

WHERE

payments.booking_id = ?

";



$result = $conn->execute_query($get_payment,[

    $booking_id

]);



if($result->num_rows == 0){

    die("Payment Record Not Found");

}


$payment = $result->fetch_assoc();


// =======================================
// Check Owner Permission
// =======================================


if($payment['owner_id'] != $owner_id){

    die("You are not allowed to confirm this payment");

}


// =======================================
// Check Booking Status
// =======================================


if($payment['booking_status'] != 'approved'){

    die("This booking is not approved yet.");

}




// =======================================
// Check Payment Status
// =======================================


if($payment['payment_status'] == 'paid'){

    die("Payment already confirmed.");

}




// =======================================
// Check Pickup Date
// =======================================


$current_time = date("Y-m-d H:i:s");


if($payment['pickup_datetime'] > $current_time){

    die("Pickup time has not arrived yet.");

}


$conn->begin_transaction();

try {

    // Update Payment Status

    $update_payment = "

    UPDATE payments

    SET payment_status = 'paid'

    WHERE id = ?

    ";


    $conn->execute_query($update_payment,[

        $payment['payment_id']

    ]);



    // Update Owner Balance

    $update_balance = "

    UPDATE owners

    SET balance = balance + ?

    WHERE id = ?

    ";


    $conn->execute_query($update_balance,[

        $payment['amount'],

        $owner_id

    ]);


    // Save Changes

    $conn->commit();

}

catch(Exception $e){


    // Undo all changes

    $conn->rollback();


    die("Payment confirmation failed.");

}

// =======================================
// Success Message
// =======================================

echo "

<script>

alert('Payment confirmed successfully.');

window.location='../OwnerCar/OwnerBookingRequests.php';

</script>

";


?>