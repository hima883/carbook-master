<?php

require_once '../mysql/db_connect.php';


// =======================================
// Owner ID
// =======================================

$owner_id = 1;

// Replace with $_SESSION['owner_id'] after creating Owner Login Session



// =======================================
// Get Pending Booking Requests
// =======================================

$query = "

SELECT

bookings.id AS booking_id,

bookings.pickup_datetime,

bookings.return_datetime,

bookings.total_price,

bookings.booking_status,

cars.id AS car_id,

cars.make,

cars.model,

cars.image,

tenants.name,

tenants.phone,

tenants.driving_license,

tenants.damages_count ,

payments.payment_status

FROM bookings

INNER JOIN cars

ON bookings.car_id = cars.id

INNER JOIN tenants

ON bookings.tenant_license = tenants.driving_license

LEFT JOIN payments

ON bookings.id = payments.booking_id

WHERE

cars.owner_id = ?

AND

bookings.booking_status IN ('pending','approved')

ORDER BY

tenants.damages_count ASC,

bookings.id ASC

";


$requests = $conn->execute_query($query, [

    $owner_id

]);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Booking Requests</title>

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

    width:90%;
    margin:40px auto;

}

.page-title{

    text-align:center;
    margin-bottom:35px;

}

.page-title h1{

    color:#333;
    margin-bottom:10px;

}

.page-title p{

    color:#666;

}

.requests{

    display:grid;

    grid-template-columns:repeat(auto-fill,minmax(420px,1fr));

    gap:30px;

}

.card{

    background:white;

    border-radius:15px;

    overflow:hidden;

    box-shadow:0 5px 15px rgba(0,0,0,.15);

    transition:.3s;

}

.card:hover{

    transform:translateY(-8px);

}

.card img{

    width:100%;

    height:240px;

    object-fit:cover;

}

.info{

    padding:20px;

}

.car-title{

    font-size:24px;

    font-weight:bold;

    color:#333;

    margin-bottom:20px;

}

.info p{

    margin-bottom:12px;

    color:#555;

    font-size:15px;

}

.price{

    color:#28a745;

    font-size:24px;

    font-weight:bold;

    margin-top:15px;

}

.damage-good{

    color:#198754;

    font-weight:bold;

}

.damage-bad{

    color:#dc3545;

    font-weight:bold;

}

.buttons{

    display:flex;

    gap:15px;

    margin-top:25px;

}

.approve-btn{

    flex:1;

    text-align:center;

    text-decoration:none;

    background:#198754;

    color:white;

    padding:12px;

    border-radius:8px;

    transition:.3s;

}

.approve-btn:hover{

    background:#157347;

}

.reject-btn{

    flex:1;

    text-align:center;

    text-decoration:none;

    background:#dc3545;

    color:white;

    padding:12px;

    border-radius:8px;

    transition:.3s;

}

.reject-btn:hover{

    background:#bb2d3b;

}

.no-requests{

    grid-column:1/-1;

    background:white;

    padding:40px;

    border-radius:15px;

    text-align:center;

    box-shadow:0 5px 15px rgba(0,0,0,.15);

}

</style>

</head>

<body>

<div class="container">

<div class="page-title">

<h1>

Booking Requests

</h1>

<p>

Review pending booking requests from tenants.

</p>

</div>

<div class="requests">

<?php

if ($requests->num_rows > 0) {

    while ($row = $requests->fetch_assoc()) {

?>

<div class="card">

    <img src="<?= $row['image'] ?>" alt="Car Image">

    <div class="info">

        <div class="car-title">

            <?= $row['make'] . " " . $row['model'] ?>

        </div>

        <p>

            <strong>Tenant Name:</strong>

            <?= $row['name'] ?>

        </p>

        <p>

            <strong>Driving License:</strong>

            <?= $row['driving_license'] ?>

        </p>

        <p>

            <strong>Phone:</strong>

            <?= $row['phone'] ?>

        </p>

        <p>

            <strong>Pickup:</strong>

            <?= $row['pickup_datetime'] ?>

        </p>

        <p>

            <strong>Return:</strong>

            <?= $row['return_datetime'] ?>

        </p>

        <p>

            <strong>Previous Damages:</strong>

            <?php

            if ($row['damages_count'] == 0) {

            ?>

                <span class="damage-good">

                    <?= $row['damages_count'] ?>

                </span>

            <?php

            }

            else {

            ?>

                <span class="damage-bad">

                    <?= $row['damages_count'] ?>

                </span>

            <?php

            }

            ?>

        </p>

        <div class="price">

            <?= number_format($row['total_price'],2) ?>

            EGP

        </div>

  
<div class="buttons">

<?php

if($row['booking_status'] == 'pending'){

?>

    <a

    href="OwnerApproveBooking.php?booking_id=<?= $row['booking_id'] ?>"

    class="approve-btn">

        Approve

    </a>


    <a

    href="OwnerRejectBooking.php?booking_id=<?= $row['booking_id'] ?>"

    class="reject-btn"

    onclick="return confirm('Are you sure you want to reject this booking request?');">

        Reject

    </a>

<?php

}

else{

?>

    <p>

        <strong>Payment Status:</strong>

        <?= ucfirst($row['payment_status']) ?>

    </p>

<?php

// test here -> time matching for pickup date and time with the current server time to allow the owner to confirm payment only after the pickup date and time has passed
    // echo "<hr>";

// echo "Booking Status: " . $row['booking_status'];

// echo "<br>";

// echo "Payment Status: ";

// var_dump($row['payment_status']);

// echo "<br>";

// echo "Server Time: " . date("Y-m-d H:i:s");

// echo "<br>";

// echo "Pickup Time: " . $row['pickup_datetime'];

// echo "<br>";

// echo "Time Compare: ";

// var_dump(strtotime(date("Y-m-d H:i:s")) >= strtotime($row['pickup_datetime']));

// echo "<hr>";

// end test  - > don't forget to remove this after testing

    if($row['payment_status'] == 'pending'){

        if(strtotime(date("Y-m-d H:i:s")) >= strtotime($row['pickup_datetime'])){

?>

            <a

            href="../OwnerCar/OwnerConfirmPayment.php?booking_id=<?= $row['booking_id'] ?>"

            class="approve-btn">

                Confirm Payment

            </a>

<?php

        }

        else{

?>

            <span>

                Waiting For Pickup Date

            </span>

<?php

        }

    }

    else{

?>

        <span>

            ✅ Payment Completed

        </span>

<?php

    }

}

?>

</div>

    </div>

</div>

<?php

    }

}

else {

?>

<div class="no-requests">

    <h2>

        <!-- No Pending Booking Requests -->

        No Booking Requests Found

    </h2>

    <br>

    <p>

        There are currently no pending or approved booking requests.

    </p>

</div>

<?php

}

?>

</div>

</div>

</body>

</html>