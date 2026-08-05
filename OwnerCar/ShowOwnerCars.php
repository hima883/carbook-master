

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>See Your Cars</title>

    <style>

.card{
    transition: .3s;
}

.card:hover{
    transform: translateY(-8px);
    box-shadow:0 10px 25px rgba(0,0,0,.15)!important;
}

.card-img-top{
    border-top-left-radius:16px;
    border-top-right-radius:16px;
}

    </style>
    
</head>
<body> 
</body>
</html>




<?php


require_once '../mysql/db_connect.php';

$stmt = "SELECT * FROM cars WHERE owner_id = ?";
$owner_cars = $conn->execute_query($stmt, [1]); // استبدل 1 بـ $_SESSION['owner_id']
?>

<div class="container mt-4">
    <div class="row g-4">

        <?php while ($row = $owner_cars->fetch_assoc()) { ?>

            <div class="col-md-4 col-lg-3">
                <div class="card h-100 shadow border-0 rounded-4">

                    <img src="<?= $row['image'] ?>"
                        class="card-img-top"
                        style="height:220px; object-fit:cover;"
                        alt="Car Image">

                    <div class="card-body">

                        <h5 class="card-title fw-bold">
                            <?= $row['make'] . " " . $row['model'] ?>
                        </h5>

                        <p class="text-muted mb-2">
                            <?= "Model Year: " . $row['model_year'] ?>
                        </p>

                        <p class="mb-1">
                            <i class="bi bi-palette-fill text-primary"></i>
                            <?= "Color: " . $row['color'] ?>
                        </p>

                        <p class="mb-1">
                            <i class="bi bi-credit-card-2-front-fill text-secondary"></i>
                            <?= "Plate Number: " . $row['plate_number'] ?>
                        </p>

                        <h5 class="text-success mt-3">
                            <?= "Daily Rent: " . $row['daily_rent'] ?>
                            <small class="text-muted fs-6">/ Day</small>
                        </h5>

                    </div>

                    <div class="card-footer bg-white border-0 d-flex justify-content-between">

                        <a href="../OwnerCar/EditCarInfo.php?car_id=<?= $row['id']?>"
                            class="btn btn-warning btn-sm">
                            Edit
                        </a>

                        <span style="margin-right: 10px;"> </span>

                        <a href="../OwnerCar/RemoveCar.php?car_id=<?= $row['id'] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Delete this car?')">
                            Delete
                        </a>

                    </div>

                </div>
            </div>

        <?php } ?>

    </div>
</div>







