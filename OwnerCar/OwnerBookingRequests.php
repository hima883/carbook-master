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
    die("You must be an owner to view booking requests.");
}

$check_owner->close();

$owner_id = $user_id;



// =======================================
// Get Pending + Approved Booking Requests
// =======================================

$query = "
SELECT

    bookings.id AS booking_id,
    bookings.pickup_datetime,
    bookings.return_datetime,
    bookings.total_price,
    bookings.daily_rent,
    bookings.booking_status,

    cars.id AS car_id,
    cars.brand,
    cars.model,
    cars.image,

    users.name,
    users.phone,

    tenants.driving_license,
    tenants.damages_count,

    payments.payment_status

FROM bookings

INNER JOIN cars
    ON bookings.car_id = cars.id

INNER JOIN tenants
    ON bookings.user_id = tenants.user_id

INNER JOIN users
    ON tenants.user_id = users.id

LEFT JOIN payments
    ON bookings.id = payments.booking_id

WHERE
    cars.owner_id = ?

AND
    bookings.booking_status IN ('pending', 'approved')

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
.status-message {

    flex: 1;

    text-align: center;

    background: #6c757d;

    color: white;

    padding: 12px;

    border-radius: 8px;

    font-weight: bold;

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
    Review and manage booking requests from tenants.
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

            <?= htmlspecialchars($row['brand'] . " " . $row['model']) ?>

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
    <strong>Booking Status:</strong>

    <?= htmlspecialchars(ucfirst($row['booking_status'])) ?>
</p>

<?php if ($row['booking_status'] === 'approved') { ?>

<p>
    <strong>Payment Status:</strong>

    <?= htmlspecialchars(
        ucfirst($row['payment_status'] ?? 'pending')
    ) ?>
</p>

<?php } ?>

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

        

        <p>
        <strong>Daily Rent:</strong>
        <?= number_format((float)$row['daily_rent'], 2) ?> EGP
        </p>

        <div class="price">
            Total:
            <?= number_format((float)$row['total_price'], 2) ?> EGP
        </div>

        <div class="buttons">

<?php

// =======================================
// Pending Booking
// =======================================

if ($row['booking_status'] === 'pending') {

?>

    <a
        href="OwnerApproveBooking.php?booking_id=<?= (int)$row['booking_id'] ?>"
        class="approve-btn">
        Approve
    </a>

    <a
        href="OwnerRejectBooking.php?booking_id=<?= (int)$row['booking_id'] ?>"
        class="reject-btn"
        onclick="return confirm('Are you sure you want to reject this booking request?');">
        Reject
    </a>

<?php

}

// =======================================
// Approved Booking
// =======================================

elseif ($row['booking_status'] === 'approved') {

    $current_time = time();
    $pickup_time = strtotime($row['pickup_datetime']);
    $return_time = strtotime($row['return_datetime']);

    // ===================================
    // Payment Not Confirmed
    // ===================================

    if ($row['payment_status'] !== 'paid') {

        if ($current_time >= $pickup_time) {

?>

            <a
                href="OwnerConfirmPayment.php?booking_id=<?= (int)$row['booking_id'] ?>"
                class="approve-btn"
                onclick="return confirm('Confirm that payment has been received?');">
                Confirm Payment
            </a>

<?php

        }

        else {

?>

            <span class="status-message">
                Waiting for pickup date
            </span>

<?php

        }

    }

    // ===================================
    // Payment Confirmed
    // ===================================

    else {

        if ($current_time >= $return_time) {

?>

            <a
                href="OwnerCompleteBooking.php?booking_id=<?= (int)$row['booking_id'] ?>"
                class="approve-btn"
                onclick="return confirm('Complete this booking with no damages?');">
                Complete Booking
            </a>

            <a
                href="OwnerReportDamages.php?booking_id=<?= (int)$row['booking_id'] ?>"
                class="reject-btn">
                Report Damages
            </a>

<?php

        }

        else {

?>

            <span class="status-message">
                Payment Confirmed - Waiting for return date
            </span>

<?php

        }

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

        No Pending Booking Requests

    </h2>

    <br>

    <p>

        There are currently no booking requests waiting for your approval.

    </p>

</div>

<?php

}

?>

</div>

</div>

</body>

</html>