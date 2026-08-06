<?php

require_once '../config/auth.php';

require_login('../login.php');

$user_id = $_SESSION['user_id'];

// Only owners can access this page
$check_owner = $conn->prepare("
    SELECT user_id
    FROM owners
    WHERE user_id = ?
");

$check_owner->bind_param("i", $user_id);
$check_owner->execute();

$owner_result = $check_owner->get_result();

if ($owner_result->num_rows === 0) {
    die("You must be an owner to add a car.");
}

$check_owner->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Car</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            width: 520px;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .icon {
            width: 90px;
            height: 60px;
            margin: auto;
            background: #f0f0ff;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 35px;
        }

        h1 {
            text-align: center;
            margin-top: 25px;
            color: #1f2937;
        }

        .subtitle {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 30px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #374151;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        input,
        select {
            height: 42px;
        }

        textarea {
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #6c63ff;
        }   

        .row {
            display: flex;
            gap: 15px;
        }

        .row div {
            width: 50%;
        }

        .file-input {
            padding: 8px;
            height: auto;
        }

        button {
            width: 100%;
            height: 45px;
            background: #6c63ff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #584ff0;
        }

    </style>
</head>


<body>

<div class="container">

    <div class="icon">
        🚗
    </div>

    <h1>Add Car</h1>

    <p class="subtitle">
        Please provide your car details below.
    </p>


    <form action="StoreCarDataToDB.php" method="POST" enctype="multipart/form-data">

    <!-- Brand -->
    <label>Car Brand *</label>
    <input type="text"
           name="car_brand"
           placeholder="Example: Toyota"
           required>


    <!-- Model -->
    <label>Car Model *</label>
    <input type="text"
           name="car_model"
           placeholder="Example: Corolla"
           required>


    <!-- Plate Number -->
    <label>Plate Number *</label>
    <input type="text"
           name="car_plate_number"
           placeholder="ABC-1234"
           required>


    <!-- Year + Color -->
    <div class="row">

        <div>
            <label>Model Year *</label>

            <input type="number"
                   min="1900"
                   max="<?= date('Y') + 1 ?>"
                   name="car_year"
                   placeholder="2024"
                   required>
        </div>

        <div>
            <label>Color *</label>

            <input type="text"
                   name="car_color"
                   placeholder="Black"
                   required>
        </div>

    </div>


    <!-- Location -->
    <label>Location *</label>

    <input type="text"
           name="car_location"
           placeholder="Example: Cairo"
           required>


    <!-- Mileage + Seats -->
    <div class="row">

        <div>
            <label>Mileage (KM) *</label>

            <input type="number"
                   min="0"
                   name="car_mileage"
                   placeholder="50000"
                   required>
        </div>

        <div>
            <label>Seats *</label>

            <input type="number"
                   min="1"
                   max="20"
                   name="car_seats"
                   placeholder="5"
                   required>
        </div>

    </div>


    <!-- Fuel Type -->
    <label>Fuel Type *</label>

    <select name="car_fuel_type" required>
        <option value="">Select Fuel Type</option>
        <option value="petrol">Petrol</option>
        <option value="diesel">Diesel</option>
        <option value="electric">Electric</option>
    </select>


    <!-- Transmission -->
    <label>Transmission *</label>

    <select name="car_transmission" required>
        <option value="">Select Transmission</option>
        <option value="automatic">Automatic</option>
        <option value="manual">Manual</option>
    </select>


    <!-- Price -->
    <label>Price Per Day (EGP) *</label>

    <input type="number"
           min="1"
           step="0.01"
           name="car_price_per_day"
           placeholder="Example: 1500"
           required>


    <!-- Description -->
    <label>Description</label>

    <textarea name="car_description"
              rows="4"
              placeholder="Write some information about the car..."></textarea>


    <!-- Image -->
    <label>Car Image *</label>

    <input type="file"
           name="car_image"
           class="file-input"
           accept="image/*"
           required>


    <button type="submit">
        Add Car
    </button>

</form>

</div>


</body>
</html>
