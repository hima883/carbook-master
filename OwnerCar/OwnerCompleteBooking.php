<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');

// =======================================
// Get Logged-in Owner
// =======================================

$user_id = $_SESSION['user_id'];


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
    die("You must be an owner to complete bookings.");
}

$check_owner->close();

$owner_id = $user_id;


// =======================================
// Get Booking ID
// =======================================

if (!isset($_GET['booking_id'])) {
    die("Booking ID is missing.");
}

$booking_id = (int) $_GET['booking_id'];

if ($booking_id <= 0) {
    die("Invalid Booking ID.");
}


// =======================================
// Get Booking
// =======================================

$get_booking = "
    SELECT
        bookings.id AS booking_id,
        bookings.booking_status,
        bookings.return_datetime,

        cars.id AS car_id,
        cars.owner_id,

        payments.payment_status

    FROM bookings

    INNER JOIN cars
        ON bookings.car_id = cars.id

    INNER JOIN payments
        ON bookings.id = payments.booking_id

    WHERE bookings.id = ?
    AND cars.owner_id = ?
    AND bookings.booking_status = 'approved'

    LIMIT 1
";

$booking = $conn->execute_query($get_booking, [
    $booking_id,
    $owner_id
]);


// =======================================
// Make Sure Booking Exists
// =======================================

if ($booking->num_rows === 0) {
    die("Booking not found, already completed, or you are not allowed to complete it.");
}

$row = $booking->fetch_assoc();


// =======================================
// Check Payment
// =======================================

if ($row['payment_status'] !== 'paid') {
    die("Payment has not been confirmed yet.");
}


// =======================================
// Check Return Date
// =======================================

$current_time = time();
$return_time = strtotime($row['return_datetime']);

if ($current_time < $return_time) {
    die("The return date has not arrived yet.");
}


// =======================================
// Complete Booking
// =======================================

$conn->begin_transaction();

try {

    // -----------------------------------
    // Mark Booking As Completed
    // -----------------------------------

    $complete_booking = "
        UPDATE bookings

        SET booking_status = 'completed'

        WHERE id = ?
        AND booking_status = 'approved'
    ";

    $conn->execute_query($complete_booking, [
        $booking_id
    ]);

    if ($conn->affected_rows !== 1) {
        throw new Exception("Booking could not be completed.");
    }


    // -----------------------------------
    // Make Car Available Again
    // -----------------------------------

    $update_car = "
        UPDATE cars

        SET status = 'available'

        WHERE id = ?
        AND owner_id = ?
    ";

    $conn->execute_query($update_car, [
        $row['car_id'],
        $owner_id
    ]);

    if ($conn->affected_rows !== 1) {
        throw new Exception("Car status could not be updated.");
    }


    // -----------------------------------
    // Save Changes
    // -----------------------------------

    $conn->commit();

}
catch (Exception $e) {

    $conn->rollback();

    die("Failed to complete booking.");
}


// =======================================
// Return To Booking Requests
// =======================================

header("Location: OwnerBookingRequests.php");
exit();

?>