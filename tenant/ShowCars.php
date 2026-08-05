<?php

require_once '../mysql/db_connect.php';


// =========================================
// Get Available Cars
// =========================================

$query = "

SELECT

cars.*,

owners.name AS owner_name

FROM cars

INNER JOIN owners

ON cars.owner_id = owners.id

WHERE cars.status = 'available'

ORDER BY cars.id DESC

";


$cars = $conn->execute_query($query);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Available Cars</title>

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


.title{

    text-align:center;
    margin-bottom:40px;

}


.title h1{

    color:#333;
    margin-bottom:10px;

}


.title p{

    color:#777;

}


.cars{

    display:grid;

    grid-template-columns:repeat(auto-fill,minmax(330px,1fr));

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

    height:220px;

    object-fit:cover;

}


.info{

    padding:20px;

}


.car-name{

    font-size:23px;

    font-weight:bold;

    color:#333;

    margin-bottom:15px;

}


.info p{

    margin-bottom:10px;

    color:#555;

    font-size:15px;

}


.price{

    color:#28a745;

    font-size:22px;

    font-weight:bold;

    margin-top:20px;

}


.owner{

    color:#0d6efd;

    font-weight:bold;

}


.details-btn{

    display:block;

    width:100%;

    margin-top:20px;

    text-align:center;

    text-decoration:none;

    background:#0d6efd;

    color:white;

    padding:12px;

    border-radius:8px;

    transition:.3s;

}


.details-btn:hover{

    background:#084298;

}

</style>

</head>

<body>


<div class="container">


<div class="title">

<h1>Available Cars</h1>

<p>

Choose your favorite car and start your journey.

</p>

</div>


<div class="cars">

<?php

if ($cars->num_rows > 0) {

    while ($row = $cars->fetch_assoc()) {

?>

<div class="card">

    <img src="<?= $row['image'] ?>" alt="Car Image">

    <div class="info">

        <div class="car-name">

            <?= $row['make'] . " " . $row['model'] ?>

        </div>

        <p>

            <strong>Model Year:</strong>

            <?= $row['model_year'] ?>

        </p>

        <p>

            <strong>Color:</strong>

            <?= $row['color'] ?>

        </p>

        <p class="owner">

            <strong>Owner:</strong>

            <?= $row['owner_name'] ?>

        </p>

        <div class="price">

            <?= number_format($row['daily_rent'],2) ?>

            EGP / Day

        </div>

        <a

        href="CarDetails.php?car_id=<?= $row['id'] ?>"

        class="details-btn">

            View Details

        </a>

    </div>

</div>

<?php

    }

}

else {

?>

<div style="grid-column:1/-1;text-align:center;">

    <h2>No Available Cars</h2>

    <p>

        There are currently no cars available for rent.

    </p>

</div>

<?php

}

?>

</div>

</div>

</body>

</html>