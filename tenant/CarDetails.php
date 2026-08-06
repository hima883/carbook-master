<?php

require_once '../config/auth.php';

require_login('../login.php');

$user_id = $_SESSION['user_id'];


// =====================================
// Check Car ID
// =====================================

if (!isset($_GET['car_id'])) {

    die("Car ID Not Found");

}


$car_id = (int) $_GET['car_id'];

if ($car_id <= 0) {
    die("Invalid Car ID");
}



// =====================================
// Get Car Details
// =====================================

$query = "
SELECT
    cars.*,
    users.name AS owner_name,
    users.phone AS owner_phone

FROM cars

INNER JOIN owners
    ON cars.owner_id = owners.user_id

INNER JOIN users
    ON owners.user_id = users.id

WHERE cars.id = ?
";


$result = $conn->execute_query($query, [

    $car_id

]);



if ($result->num_rows == 0) {

    die("Car Not Found");

}



$car = $result->fetch_assoc();


?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Car Details</title>



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

    width:85%;

    margin:40px auto;

}



.details-card{

    background:white;

    border-radius:20px;

    overflow:hidden;

    box-shadow:0 5px 20px rgba(0,0,0,.15);

    display:flex;

    flex-wrap:wrap;

}



.image-section{

    width:50%;

}



.image-section img{

    width:100%;

    height:400px;

    object-fit:cover;

}



.info-section{

    width:50%;

    padding:35px;

}



.car-title{

    font-size:32px;

    color:#333;

    margin-bottom:25px;

}



.info{

    margin-bottom:15px;

    font-size:17px;

    color:#555;

}



.info strong{

    color:#222;

}



.price{

    margin-top:25px;

    font-size:28px;

    color:#28a745;

    font-weight:bold;

}



.owner-box{

    margin-top:25px;

    padding:15px;

    background:#f1f5ff;

    border-radius:10px;

}



.owner-box h3{

    margin-bottom:10px;

    color:#0d6efd;

}



.book-btn{

    display:block;

    margin-top:30px;

    text-align:center;

    text-decoration:none;

    background:#0d6efd;

    color:white;

    padding:14px;

    border-radius:10px;

    font-size:18px;

    font-weight:bold;

    transition:.3s;

}



.book-btn:hover{

    background:#084298;

}



@media(max-width:800px){


    .image-section,

    .info-section{

        width:100%;

    }


}


</style>


</head>


<body>


<div class="container">


<div class="details-card">

<div class="image-section">

    <img src="<?= htmlspecialchars($car['image']) ?>" alt="Car Image">

</div>



<div class="info-section">


<h1 class="car-title">

    <?= htmlspecialchars($car['brand'] . " " . $car['model']) ?>

</h1>



<div class="info">

    <strong>Model Year:</strong>

    <?= $car['year'] ?>

</div>



<div class="info">

    <strong>Color:</strong>

    <?= $car['color'] ?>

</div>

<div class="info">
    <strong>Location:</strong>
    <?= htmlspecialchars($car['location']) ?>
</div>

<div class="info">
    <strong>Mileage:</strong>
    <?= number_format((int)$car['mileage']) ?> KM
</div>

<div class="info">
    <strong>Fuel Type:</strong>
    <?= htmlspecialchars(ucfirst($car['fuel_type'])) ?>
</div>

<div class="info">
    <strong>Transmission:</strong>
    <?= htmlspecialchars(ucfirst($car['transmission'])) ?>
</div>

<div class="info">
    <strong>Seats:</strong>
    <?= (int)$car['seats'] ?>
</div>


<div class="info">

    <strong>Plate Number:</strong>

    <?= $car['plate_number'] ?>

</div>



<div class="info">

    <strong>Status:</strong>

    <?= $car['status'] ?>

</div>



<div class="price">

    <?= number_format($car['price_per_day'], 2) ?>

    EGP / Day

</div>

<?php if (!empty($car['description'])): ?>

<div class="info" style="margin-top:20px;">
    <strong>Description:</strong><br><br>
    <?= nl2br(htmlspecialchars($car['description'])) ?>
</div>

<?php endif; ?>



<div class="owner-box">


<h3>

Owner Information

</h3>


<div class="info">

    <strong>Name:</strong>

    <?= htmlspecialchars($car['owner_name']) ?>

</div>



<div class="info">

    <strong>Phone:</strong>

    <?= htmlspecialchars($car['owner_phone']) ?>

</div>



</div>





<?php if ($car['status'] === 'available'): ?>

    <a
        href="BookCar.php?car_id=<?= (int)$car['id'] ?>"
        class="book-btn">

        Book Now

    </a>

<?php else: ?>

    <div style="
        margin-top:30px;
        padding:14px;
        background:#dc3545;
        color:white;
        text-align:center;
        border-radius:10px;
        font-weight:bold;
    ">
        This car is currently unavailable
    </div>

<?php endif; ?>



</div>


</div>


</div>


</body>


</html>