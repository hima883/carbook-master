<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');

$user_id = $_SESSION['user_id'];

// Make sure the logged-in user is a tenant
$tenant_check = $conn->prepare("
    SELECT user_id
    FROM tenants
    WHERE user_id = ?
");

$tenant_check->bind_param("i", $user_id);
$tenant_check->execute();

$tenant_result = $tenant_check->get_result();

if ($tenant_result->num_rows === 0) {
    die("You must be a tenant to book a car.");
}

$tenant_check->close();



// =======================================
// Check Car ID
// =======================================

if (!isset($_GET['car_id'])) {

    die("Car ID Not Found");

}


$car_id = $_GET['car_id'];



// =======================================
// Get Car Data
// =======================================

$query = "

SELECT *

FROM cars

WHERE id = ?

AND status = 'available'

";


$result = $conn->execute_query($query, [

    $car_id

]);



if ($result->num_rows == 0) {

    die("Car is not available");

}


$car = $result->fetch_assoc();


// =======================================
// Store Booking
// =======================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    $pickup_datetime = $_POST['pickup_datetime'];

    $return_datetime = $_POST['return_datetime'];

    // =======================================
// Validate Dates
// =======================================

if (empty($pickup_datetime) || empty($return_datetime)) {
    die("Pickup and return dates are required.");
}

$pickup = new DateTime($pickup_datetime);
$return = new DateTime($return_datetime);
$now = new DateTime();

if ($pickup < $now) {
    die("Pickup date cannot be before today.");
}

if ($return <= $pickup) {
    die("Return date must be after pickup date.");
}


// =======================================
// Check Booking Conflict
// =======================================

$check_booking = "
    SELECT id
    FROM bookings

    WHERE car_id = ?

    AND booking_status = 'approved'

    AND pickup_datetime < ?
    AND return_datetime > ?

    LIMIT 1
";

$existing_booking = $conn->execute_query($check_booking, [
    $car_id,
    $return_datetime,
    $pickup_datetime
]);

if ($existing_booking->num_rows > 0) {
    die("This car is already booked during the selected period.");
}




// =======================================
// Calculate Total Price
// =======================================

$daily_rent = (float) $car['price_per_day'];

$seconds = $return->getTimestamp() - $pickup->getTimestamp();

$days = (int) ceil($seconds / 86400);

if ($days < 1) {
    $days = 1;
}

$total_price = $days * $daily_rent;

    
// =======================================
// Insert Booking
// =======================================

$insert = "
INSERT INTO bookings
(
    user_id,
    car_id,
    pickup_datetime,
    return_datetime,
    daily_rent,
    total_price,
    booking_status
)
VALUES
(
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    'pending'
)
";

$conn->execute_query($insert, [
    $user_id,
    $car_id,
    $pickup_datetime,
    $return_datetime,
    $daily_rent,
    $total_price
]);



echo "

<script>

alert('Booking request sent successfully.');

window.location='ShowCars.php';

</script>

";

exit();


}


?>


<!DOCTYPE html>

<html lang="en">


<head>


<meta charset="UTF-8">


<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>Book Car</title>

<style>

*{

    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;

}


body{

    background:#f4f6f9;

}



.container{

    width:600px;

    margin:40px auto;

    background:white;

    padding:30px;

    border-radius:15px;

    box-shadow:0 5px 20px rgba(0,0,0,.15);

}



.car-image{

    text-align:center;

    margin-bottom:20px;

}



.car-image img{

    width:300px;

    height:200px;

    object-fit:cover;

    border-radius:15px;

}



h1{

    text-align:center;

    color:#333;

    margin-bottom:25px;

}



.car-info{

    background:#f8f9fa;

    padding:20px;

    border-radius:10px;

    margin-bottom:25px;

}



.car-info p{

    margin-bottom:10px;

    color:#555;

    font-size:16px;

}



.price{

    color:#28a745;

    font-size:24px;

    font-weight:bold;

}



label{

    display:block;

    margin-bottom:8px;

    font-weight:bold;

    color:#333;

}



input{

    width:100%;

    padding:12px;

    border:1px solid #ccc;

    border-radius:8px;

    margin-bottom:20px;

    font-size:15px;

}



button{

    width:100%;

    padding:14px;

    background:#0d6efd;

    color:white;

    border:none;

    border-radius:8px;

    font-size:17px;

    font-weight:bold;

    cursor:pointer;

}



button:hover{

    background:#084298;

}



</style>


</head>


<body>


<div class="container">


<h1>

Book Car

</h1>



<div class="car-image">

<img src="<?= $car['image'] ?>" alt="Car Image">

</div>




<div class="car-info">


<p>

<strong>Car:</strong>

<?= $car['brand'] . " " . $car['model'] ?>

</p>



<p>

<strong>Model Year:</strong>

<?= $car['year'] ?>

</p>



<p>

<strong>Color:</strong>

<?= $car['color'] ?>

</p>



<p class="price">

<?= number_format($car['price_per_day'], 2) ?>

EGP / Day

</p>


</div>




<form method="POST">

<!-- <label> Pickup Date & Time </label>
<input type="datetime-local" name="pickup_datetime" required>

<label> Return Date & Time </label>
<input type="datetime-local" name="return_datetime" required> -->

<?php

$today = date("Y-m-d\TH:i");

?>

<label>
Pickup Date & Time
</label>

<input
type="datetime-local"
name="pickup_datetime"
min="<?= $today ?>"
required>

<label>
Return Date & Time
</label>

<input
type="datetime-local"
name="return_datetime"
min="<?= $today ?>"
required>


<button type="submit">

Confirm Booking

</button>

</form>

</div>

</body>

</html>