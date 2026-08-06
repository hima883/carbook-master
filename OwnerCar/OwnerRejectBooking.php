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
    die("You must be an owner to reject bookings.");
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
    bookings.id,
    bookings.booking_status,
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
    die("Booking not found, already processed, or you are not allowed to reject it.");
}


// =======================================
// Reject Booking
// =======================================


$reject_booking = "
UPDATE bookings
SET booking_status = 'cancelled'
WHERE id = ?
AND booking_status = 'pending'
";

$conn->execute_query($reject_booking, [
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