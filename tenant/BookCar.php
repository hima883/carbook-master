<?php

require_once '../mysql/db_connect.php';


// =======================================
// Tenant License
// =======================================

$tenant_license = 1; 
// Replace with $_SESSION['tenant_license'] after creating Tenant Login Session



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
// Check Booking Date Conflict
// =======================================

$check_query = "

SELECT id

FROM bookings

WHERE car_id = ?

AND booking_status = 'approved'

AND (

    pickup_datetime <= ?

    AND

    return_datetime >= ?

)"
;

$check_result = $conn->execute_query($check_query,[

    $car_id,

    $return_datetime,

    $pickup_datetime

]);



if($check_result->num_rows > 0){

    die("This car is already booked during the selected period.");

}


    $today = date("Y-m-d H:i:s");

if ($pickup_datetime < $today) {

    die("Pickup date cannot be before today.");

}

if ($return_datetime <= $pickup_datetime) {

    die("Return date must be after pickup date.");

}

    $daily_rent = $car['daily_rent'];



    $start = new DateTime($pickup_datetime);

    $end = new DateTime($return_datetime);



    $difference = $start->diff($end);



    $days = $difference->days;



    if ($days == 0) {

        $days = 1;

    }



    $total_price = $days * $daily_rent;

    
// =======================================
// Insert Booking
// =======================================

$insert = "

INSERT INTO bookings

(
    tenant_license,
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

    $tenant_license,

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

<?= $car['make'] . " " . $car['model'] ?>

</p>



<p>

<strong>Model Year:</strong>

<?= $car['model_year'] ?>

</p>



<p>

<strong>Color:</strong>

<?= $car['color'] ?>

</p>



<p class="price">

<?= number_format($car['daily_rent'],2) ?>

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