<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');


// =========================================
// Get Filters
// =========================================

$brand = trim($_GET['brand'] ?? '');
$model = trim($_GET['model'] ?? '');
$year = trim($_GET['year'] ?? '');
$color = trim($_GET['color'] ?? '');

$min_price = trim($_GET['min_price'] ?? '');
$max_price = trim($_GET['max_price'] ?? '');

$available_from = trim($_GET['available_from'] ?? '');
$available_to = trim($_GET['available_to'] ?? '');


// =========================================
// Get Available Cars
// =========================================

$query = "
SELECT DISTINCT

    cars.*,
    users.name AS owner_name

FROM cars

INNER JOIN owners
    ON cars.owner_id = owners.user_id

INNER JOIN users
    ON owners.user_id = users.id

WHERE cars.status = 'available'
";

$params = [];
$types = '';


// =========================================
// Brand Filter
// =========================================

if ($brand !== '') {

    $query .= " AND cars.brand LIKE ?";

    $params[] = '%' . $brand . '%';
    $types .= 's';
}


// =========================================
// Model Filter
// =========================================

if ($model !== '') {

    $query .= " AND cars.model LIKE ?";

    $params[] = '%' . $model . '%';
    $types .= 's';
}


// =========================================
// Year Filter
// =========================================

if ($year !== '') {

    $query .= " AND cars.year = ?";

    $params[] = (int) $year;
    $types .= 'i';
}


// =========================================
// Color Filter
// =========================================

if ($color !== '') {

    $query .= " AND cars.color LIKE ?";

    $params[] = '%' . $color . '%';
    $types .= 's';
}


// =========================================
// Minimum Price
// =========================================

if ($min_price !== '' && is_numeric($min_price)) {

    $query .= " AND cars.price_per_day >= ?";

    $params[] = (float) $min_price;
    $types .= 'd';
}


// =========================================
// Maximum Price
// =========================================

if ($max_price !== '' && is_numeric($max_price)) {

    $query .= " AND cars.price_per_day <= ?";

    $params[] = (float) $max_price;
    $types .= 'd';
}


// =========================================
// Availability Date Filter
// =========================================

if ($available_from !== '' && $available_to !== '') {

    $query .= "
    AND NOT EXISTS (

        SELECT 1

        FROM bookings

        WHERE bookings.car_id = cars.id

        AND bookings.booking_status = 'approved'

        AND bookings.pickup_datetime < ?
        AND bookings.return_datetime > ?

    )
    ";

    $params[] = $available_to;
    $params[] = $available_from;

    $types .= 'ss';
}


$query .= " ORDER BY cars.id DESC";


// =========================================
// Execute Query
// =========================================

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Failed to prepare cars query.");
}

if (!empty($params)) {

    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$cars = $stmt->get_result();

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Available Cars</title>
<link rel="stylesheet" href="css/ShowCars.css">

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

<div class="page-layout">

    <!-- ========================= -->
    <!-- Filters Sidebar -->
    <!-- ========================= -->

    <aside class="filters-sidebar">

        <div class="sidebar-header">
            Filters
        </div>

        <form method="GET" action="ShowCars.php">

            <div class="filter-section">

                <h3>Car Information</h3>

                <label>Brand</label>
                <input
                    type="text"
                    name="brand"
                    placeholder="BMW, Audi..."
                    value="<?= htmlspecialchars($brand) ?>"
                >

                <label>Model</label>
                <input
                    type="text"
                    name="model"
                    placeholder="M5, Q8..."
                    value="<?= htmlspecialchars($model) ?>"
                >

                <label>Year</label>
                <input
                    type="number"
                    name="year"
                    placeholder="2024"
                    value="<?= htmlspecialchars($year) ?>"
                >

                <label>Color</label>
                <input
                    type="text"
                    name="color"
                    placeholder="Black, White..."
                    value="<?= htmlspecialchars($color) ?>"
                >

            </div>


            <div class="filter-section">

                <h3>Price</h3>

                <label>Minimum Price</label>
                <input
                    type="number"
                    name="min_price"
                    min="0"
                    step="0.01"
                    placeholder="Min EGP"
                    value="<?= htmlspecialchars($min_price) ?>"
                >

                <label>Maximum Price</label>
                <input
                    type="number"
                    name="max_price"
                    min="0"
                    step="0.01"
                    placeholder="Max EGP"
                    value="<?= htmlspecialchars($max_price) ?>"
                >

            </div>


            <div class="filter-section">

                <h3>Availability</h3>

                <label>Available From</label>
                <input
                    type="datetime-local"
                    name="available_from"
                    value="<?= htmlspecialchars($available_from) ?>"
                >

                <label>Available To</label>
                <input
                    type="datetime-local"
                    name="available_to"
                    value="<?= htmlspecialchars($available_to) ?>"
                >

            </div>


            <button type="submit" class="search-btn">
                Search Cars
            </button>

            <a href="ShowCars.php" class="reset-btn">
                Reset Filters
            </a>

        </form>

    </aside>


    <!-- ========================= -->
    <!-- Cars -->
    <!-- ========================= -->

    <main class="cars-content">

        <div style="margin-bottom:20px; color:#555; font-size:17px;">

            <strong><?= $cars->num_rows ?></strong>

            car<?= $cars->num_rows === 1 ? '' : 's' ?> found

        </div>


        <div class="cars">

        <?php

        if ($cars->num_rows > 0) {

            while ($row = $cars->fetch_assoc()) {

        ?>

            <div class="card">

                <img
                    src="<?= htmlspecialchars($row['image']) ?>"
                    alt="Car Image"
                >

                <div class="info">

                    <div class="car-name">

                        <?= htmlspecialchars(
                            $row['brand'] . " " . $row['model']
                        ) ?>

                    </div>


                    <p>
                        <strong>Model Year:</strong>
                        <?= htmlspecialchars($row['year']) ?>
                    </p>


                    <p>
                        <strong>Color:</strong>
                        <?= htmlspecialchars($row['color']) ?>
                    </p>


                    <p>
                        <strong>Location:</strong>
                        <?= htmlspecialchars($row['location']) ?>
                    </p>


                    <p class="owner">

                        <strong>Owner:</strong>

                        <?= htmlspecialchars($row['owner_name']) ?>

                    </p>


                    <div class="price">

                        <?= number_format(
                            (float)$row['price_per_day'],
                            2
                        ) ?>

                        EGP / Day

                    </div>


                    <a
                        href="CarDetails.php?car_id=<?= $row['id'] ?>"
                        class="details-btn"
                    >
                        View Details
                    </a>

                </div>

            </div>

        <?php

            }

        } else {

        ?>

            <div style="
                grid-column:1/-1;
                text-align:center;
                padding:50px;
            ">

                <h2>No Available Cars</h2>

                <p>
                    There are currently no cars available for rent.
                </p>

            </div>

        <?php

        }

        ?>

        </div>

    </main>

</div>

<div class="cars">

<?php

if ($cars->num_rows > 0) {

    while ($row = $cars->fetch_assoc()) {

?>

<div class="card">

    <img src="<?= htmlspecialchars($row['image']) ?>" alt="Car Image">

    <div class="info">

        <div class="car-name">

            <?= htmlspecialchars($row['brand'] . " " . $row['model']) ?>

        </div>

        <p>

            <strong>Model Year:</strong>

            <?= htmlspecialchars($row['year']) ?>

        </p>

        <p>

            <strong>Color:</strong>

            <?= htmlspecialchars($row['color']) ?>

        </p>
        <p>
            <strong>Location:</strong>
            <?= htmlspecialchars($row['location']) ?>
        </p>

        <p class="owner">

            <strong>Owner:</strong>

            <?= htmlspecialchars($row['owner_name']) ?>

        </p>

        <div class="price">

            <?= number_format((float)$row['price_per_day'], 2) ?> EGP / Day

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