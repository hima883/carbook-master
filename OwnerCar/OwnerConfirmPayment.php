<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');

$user_id = $_SESSION['user_id'];

// Make sure logged-in user is an owner
$check_owner = $conn->prepare("
    SELECT user_id
    FROM owners
    WHERE user_id = ?
");

$check_owner->bind_param("i", $user_id);
$check_owner->execute();

$owner_result = $check_owner->get_result();

if ($owner_result->num_rows === 0) {
    die("You must be an owner to confirm payments.");
}

$check_owner->close();

$owner_id = $user_id;



// =======================================
// Check Booking ID
// =======================================

if(!isset($_GET['booking_id'])){

    die("Booking ID Not Found");

}


$booking_id = (int) $_GET['booking_id'];




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

WHERE payments.booking_id = ?
AND cars.owner_id = ?
";



$result = $conn->execute_query($get_payment, [
    $booking_id,
    $owner_id
]);


if ($result->num_rows === 0) {
    die("Payment not found or you are not allowed to confirm it.");
}

$payment = $result->fetch_assoc();

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
    AND payment_status = 'pending'
";

$conn->execute_query($update_payment, [
    $payment['payment_id']
]);

if ($conn->affected_rows !== 1) {
    throw new Exception("Payment already confirmed.");
}



    // Update Owner Balance

    $update_balance = "
        UPDATE owners
        SET balance = balance + ?
        WHERE user_id = ?
    ";

    $conn->execute_query($update_balance, [
        $payment['amount'],
        $owner_id
    ]);

    if ($conn->affected_rows !== 1) {
        throw new Exception("Owner balance update failed.");
    }

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
    window.location='OwnerBookingRequests.php';
</script>
";

exit();


?>