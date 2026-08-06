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
    die("You must be an owner to approve bookings.");
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
// Get Booking Information
// =======================================

$get_booking = "
SELECT
    bookings.*,
    cars.owner_id
FROM bookings

INNER JOIN cars
    ON bookings.car_id = cars.id

WHERE bookings.id = ?
AND cars.owner_id = ?
AND bookings.booking_status = 'pending'
";

$result = $conn->execute_query($get_booking, [
    $booking_id,
    $owner_id
]);

if ($result->num_rows === 0) {
    die("Booking not found, already processed, or you are not allowed to approve it.");
}

$booking = $result->fetch_assoc();

// =======================================
// Approve Booking
// =======================================

$update_booking = "
    UPDATE bookings
    SET booking_status = 'approved'
    WHERE id = ?
    AND booking_status = 'pending'
";

$conn->execute_query($update_booking, [
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
ON DUPLICATE KEY UPDATE
    amount = VALUES(amount)
";

$conn->execute_query($insert_payment, [
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

    pickup_datetime < ?

    AND

    return_datetime > ?

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

window.location='OwnerBookingRequests.php';

</script>

";


?>

