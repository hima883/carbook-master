<?php

require_once '../mysql/db_connect.php';

$get_makes = "

SELECT DISTINCT make

FROM cars

ORDER BY make

";

$makes = $conn->execute_query($get_makes);



$get_models = "

SELECT DISTINCT model

FROM cars

ORDER BY model

";

$models = $conn->execute_query($get_models);



$get_years = "

SELECT DISTINCT model_year

FROM cars

ORDER BY model_year DESC

";

$years = $conn->execute_query($get_years);



$get_colors = "

SELECT DISTINCT color

FROM cars

ORDER BY color

";

$colors = $conn->execute_query($get_colors);


$make = $_GET['make'] ?? "";

$model = $_GET['model'] ?? "";

$model_year = $_GET['model_year'] ?? "";

$color = $_GET['color'] ?? "";

$min_price = $_GET['min_price'] ?? "";

$max_price = $_GET['max_price'] ?? "";

$available_from = $_GET['available_from'] ?? "";

$available_to = $_GET['available_to'] ?? "";


$current_datetime = date("Y-m-d H:i:s");

$error_message = "";


if($available_from != "" && strtotime($available_from) < strtotime($current_datetime)){

    $error_message = "Available From cannot be in the past.";

}


if(

    $available_from != "" &&

    $available_to != "" &&

    strtotime($available_to) <= strtotime($available_from)

){

    $error_message = "Available To must be after Available From.";

}


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

";

$params = [];



if($make != ""){

    $query .= "

    AND cars.make = ?

    ";

    $params[] = $make;

}



if($model != ""){

    $query .= "

    AND cars.model = ?

    ";

    $params[] = $model;

}



if($model_year != ""){

    $query .= "

    AND cars.model_year = ?

    ";

    $params[] = $model_year;

}



if($color != ""){

    $query .= "

    AND cars.color = ?

    ";

    $params[] = $color;

}



if($min_price != ""){

    $query .= "

    AND cars.daily_rent >= ?

    ";

    $params[] = $min_price;

}



if($max_price != ""){

    $query .= "

    AND cars.daily_rent <= ?

    ";

    $params[] = $max_price;

}


if(

    $available_from != "" &&

    $available_to != "" &&

    $error_message == ""

){

    $query .= "

    AND cars.id NOT IN(

        SELECT car_id

        FROM bookings

        WHERE

        booking_status = 'approved'

        AND

        (

            pickup_datetime <= ?

            AND

            return_datetime >= ?

        )

    )

    ";

    $params[] = $available_to;

    $params[] = $available_from;

}


$query .= "

ORDER BY cars.id DESC

";



$cars = $conn->execute_query($query , $params);

$total_cars = $cars->num_rows;

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Available Cars</title>

<link rel="stylesheet" href="css/ShowCars.css">

</head>

<body>


<div class="container">

    <?php
    
    if($error_message != ""){
    
    ?>
    
    <div class="error-message">
    
        <?= $error_message ?>
    
    </div>
    
    <?php
    
    }
    
    ?>

   <div class="title">
   
   <h1>Available Cars</h1>
   
   <p>
   
   Choose your favorite car and start your journey.
   
   </p>
   
   </div>

    <div class="page-layout">
    
        <div class="filters-sidebar">
        
            <div class="sidebar-header">
        
                🔍 Search Filters
        
            </div>
        
            <form action="" method="GET">
        
                <div class="filter-section">
        
                    <h3>Car Details</h3>
        
                    <label>Make</label>
        
                    <select name="make">
                    
                        <option value="">
                    
                            All Makes
                    
                        </option>
                    
                    <?php
                    
                    while($row = $makes->fetch_assoc()){
                    
                    ?>
                    
                        <option
                        
                        value="<?= $row['make'] ?>"
                        
                        <?= ($make == $row['make']) ? "selected" : "" ?>
                        
                        >
                        
                        <?= $row['make'] ?>
                        
                        </option>
                    
                    <?php
                    
                    }
                    
                    ?>
                    
                    </select>
        
                    <label>Model</label>
        
                      <select name="model">
                    
                        <option value="">
                    
                            All Models
                    
                        </option>
                    
                    <?php
                    
                    while($row = $models->fetch_assoc()){
                    
                    ?>
                    
                        <option
                        
                        value="<?= $row['model'] ?>"
                        
                        <?= ($model == $row['model']) ? "selected" : "" ?>
                        
                        >
                        
                        <?= $row['model'] ?>
                        
                        </option>
                    
                    <?php
                    
                    }
                    
                    ?>
                    
                    </select>
        
                    <label>Model Year</label>
        
                    <select name="model_year">

                        <option value="">
                    
                            All Years
                    
                        </option>
                    
                    <?php
                    
                    while($row = $years->fetch_assoc()){
                    
                    ?>
                    
                        <option
                        
                        value="<?= $row['model_year'] ?>"
                        
                        <?= ($model_year == $row['model_year']) ? "selected" : "" ?>
                        
                        >
                        
                        <?= $row['model_year'] ?>
                        
                        </option>
                    
                    <?php
                    
                    }
                    
                    ?>
                    
                    </select>
        
                    <label>Color</label>
        
                    <select name="color">

                        <option value="">
                    
                            All Colors
                    
                        </option>
                    
                    <?php
                    
                    while($row = $colors->fetch_assoc()){
                    
                    ?>
                    
                        <option
                        
                        value="<?= $row['color'] ?>"
                        
                        <?= ($color == $row['color']) ? "selected" : "" ?>
                        
                        >
                        
                        <?= $row['color'] ?>
                        
                        </option>
                    
                    <?php
                    
                    }
                    
                    ?>
                    
                    </select>
        
                </div>
        
                <div class="filter-section">
        
                    <h3>Price</h3>
        
                    <label>Minimum Daily Rent</label>
        
                    <input
                    type="number"
                    name="min_price"
                    min="0"
                    placeholder="Minimum Price"
                    value="<?= $min_price ?>">
        
                    <label>Maximum Daily Rent</label>
        
                    <input
                    type="number"
                    name="max_price"
                    min="0"
                    placeholder="Maximum Price"
                    value="<?= $max_price ?>">

                </div>
        
                <div class="filter-section">
        
                    <h3>Availability</h3>
        
                    <label>Available From</label>
        
                    <input                   
                    type="datetime-local"
                    name="available_from"                   
                    min="<?= date('Y-m-d\TH:i') ?>"                 
                    value="<?= $available_from ?>">
                    
        
                    <label>Available To</label>
        
                    <input                  
                    type="datetime-local"
                    name="available_to"
                    min="<?= date('Y-m-d\TH:i') ?>"                   
                    value="<?= $available_to ?>">


                </div>
        
                <button
                type="submit"
                class="search-btn">
        
                    🔍 Search Cars
        
                </button>
        
                <a
                href="ShowCars.php"
                class="reset-btn">
        
                    🔄 Reset Filters
        
                </a>
        
            </form>
        
        </div>
    
        <div class="cars-content">
    
            <!-- حط هنا الكود الحالى بالكامل اللى بيعرض العربيات -->
              
                <h3>
                
                <?= $total_cars ?> Cars Found
                
                </h3>
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

                                   <p> Try changing your search filters. </p>
                   
                            <?php
                        }
                       
                    ?>
        
            </div>
            
        </div>
    </div>
</div>

</body>

</html>