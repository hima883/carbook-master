<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');

$user_id = $_SESSION['user_id'];

// Make sure logged-in user is an owner
$check_owner = $conn->prepare("\n    SELECT user_id\n    FROM owners\n    WHERE user_id = ?\n");
$check_owner->bind_param("i", $user_id);
$check_owner->execute();
$owner_result = $check_owner->get_result();

if ($owner_result->num_rows === 0) {
    die("You must be an owner to report damages.");
}

$check_owner->close();
$owner_id = $user_id;

if (!isset($_GET['booking_id'])) {
    die("Booking ID is missing.");
}

$booking_id = (int) $_GET['booking_id'];
if ($booking_id <= 0) {
    die("Invalid Booking ID.");
}

$get_booking = "
SELECT
    bookings.id AS booking_id,
    bookings.booking_status,
    bookings.pickup_datetime,
    bookings.return_datetime,

    cars.id AS car_id,
    cars.owner_id,
    cars.brand,
    cars.model,
    cars.image,
    cars.plate_number,

    payments.payment_status,

    tenants.user_id AS tenant_user_id,
    tenants.driving_license,
    tenants.damages_count,

    users.name,
    users.phone

FROM bookings
INNER JOIN cars
    ON bookings.car_id = cars.id
INNER JOIN payments
    ON bookings.id = payments.booking_id
INNER JOIN tenants
    ON bookings.user_id = tenants.user_id
INNER JOIN users
    ON tenants.user_id = users.id

WHERE bookings.id = ?
AND cars.owner_id = ?
AND bookings.booking_status = 'approved'
LIMIT 1
";

$booking = $conn->execute_query($get_booking, [
    $booking_id,
    $owner_id
]);

if ($booking->num_rows === 0) {
    die("Booking not found, already completed, or access denied.");
}

$row = $booking->fetch_assoc();

if ($row['payment_status'] !== 'paid') {
    die("Payment has not been confirmed yet.");
}

if (time() < strtotime($row['return_datetime'])) {
    die("The return date has not arrived yet.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['damage'])) {
        die("Please select the vehicle inspection result.");
    }

    $damage = (int) $_POST['damage'];
    if (!in_array($damage, [0, 1], true)) {
        die("Invalid damage value.");
    }

    $conn->begin_transaction();

    try {
        if ($damage === 1) {
            $update_tenant = "
                UPDATE tenants
                SET damages_count = damages_count + 1
                WHERE user_id = ?
            ";
            $conn->execute_query($update_tenant, [$row['tenant_user_id']]);

            if ($conn->affected_rows !== 1) {
                throw new Exception("Failed to update tenant damage count.");
            }
        }

        $complete_booking = "
            UPDATE bookings
            SET booking_status = 'completed'
            WHERE id = ?
            AND booking_status = 'approved'
        ";
        $conn->execute_query($complete_booking, [$booking_id]);

        if ($conn->affected_rows !== 1) {
            throw new Exception("Booking could not be completed.");
        }

        $update_car = "
            UPDATE cars
            SET status = 'available'
            WHERE id = ?
            AND owner_id = ?
        ";
        $conn->execute_query($update_car, [$row['car_id'], $owner_id]);

        if ($conn->affected_rows !== 1) {
            throw new Exception("Car status could not be updated.");
        }

        $conn->commit();
        header("Location: OwnerBookingRequests.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Failed to complete booking.");
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>

Report Damages

</title>

<style>

*{

    margin:0;

    padding:0;

    box-sizing:border-box;

    font-family:Arial,Helvetica,sans-serif;

}

body{

    background:#f5f5f5;

}

.container{

    width:90%;

    max-width:900px;

    margin:40px auto;

}

.card{

    background:#fff;

    border-radius:12px;

    overflow:hidden;

    box-shadow:0 5px 15px rgba(0,0,0,.15);

}

.car-image{

    width:100%;

    height:420px;

    object-fit:cover;

}

.content{

    padding:30px;

}

.content h2{

    margin-bottom:20px;

    color:#222;

}

.info{

    display:grid;

    grid-template-columns:repeat(2,1fr);

    gap:15px;

    margin-bottom:30px;

}

.info p{

    font-size:17px;

}

.damage-history{

    color:#c0392b;

    font-weight:bold;

}

.radio-box{

    background:#fafafa;

    border:1px solid #ddd;

    border-radius:10px;

    padding:20px;

    margin-bottom:30px;

}

.radio-box h3{

    margin-bottom:20px;

}

.radio-box label{

    display:block;

    margin-bottom:15px;

    font-size:18px;

    cursor:pointer;

}

.radio-box input{

    margin-right:10px;

}

.complete-btn{

    width:100%;

    padding:15px;

    border:none;

    background:#28a745;

    color:white;

    font-size:20px;

    border-radius:8px;

    cursor:pointer;

    transition:.3s;

}

.complete-btn:hover{

    background:#218838;

}

.back-btn{

    display:inline-block;

    margin-top:20px;

    text-decoration:none;

    color:#555;

}

</style>

</head>

<body>

<div class="container">

<div class="card">

<img

src="<?= htmlspecialchars($row['image']) ?>"

class="car-image"

alt="Car Image">

<div class="content">

<h2>

<?= htmlspecialchars($row['brand'] . " " . $row['model']) ?>

</h2>

<div class="info">

<p>

<strong>Plate Number:</strong>

<?= htmlspecialchars($row['plate_number']) ?>

</p>

<p>

<strong>Tenant:</strong>

<?= htmlspecialchars($row['name']) ?>

</p>

<p>

<strong>Driving License:</strong>

<?= htmlspecialchars($row['driving_license']) ?>

</p>

<p>

<strong>Phone:</strong>

<?= htmlspecialchars($row['phone']) ?>

</p>

<p>

<strong>Pickup:</strong>

<?= htmlspecialchars($row['pickup_datetime']) ?>

</p>

<p>

<strong>Return:</strong>

<?= htmlspecialchars($row['return_datetime']) ?>

</p>

<p class="damage-history">

Previous Damages :

<?= (int) $row['damages_count'] ?>

</p>

</div>

<form method="POST">

<div class="radio-box">

<h3>

Vehicle Inspection

</h3>

<label>

<input

type="radio"

name="damage"

value="0"

checked>

No Damage Found

</label>

<label>

<input

type="radio"

name="damage"

value="1">

Damage Found

</label>

</div>

<button

type="submit"

class="complete-btn"

onclick="return confirm('Are you sure you want to complete this booking?');">

Complete Booking

</button>

</form>

<a

href="OwnerBookingRequests.php"

class="back-btn">

← Back to Booking Requests

</a>

</div>

</div>

</div>

</body>

</html>