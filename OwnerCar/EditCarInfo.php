<?php

require_once '../mysql/db_connect.php';

require_once '../config/auth.php';

require_login('../login.php');

$user_id = $_SESSION['user_id'];

// ========================================
// Make sure logged-in user is an Owner
// ========================================

$check_owner = $conn->prepare("
    SELECT user_id
    FROM owners
    WHERE user_id = ?
");

$check_owner->bind_param("i", $user_id);
$check_owner->execute();

$owner_result = $check_owner->get_result();

if ($owner_result->num_rows === 0) {
    die("You must be an owner to edit a car.");
}

$check_owner->close();

$owner_id = $user_id;

// ===============================
// التأكد من وجود car_id
// ===============================

if (!isset($_GET['car_id'])) {
    die("Car ID Not Found");
}

$car_id = $_GET['car_id'];


// ======================================
// جلب بيانات العربية من قاعدة البيانات
// ======================================

$stmt = "SELECT * FROM cars WHERE id = ? AND owner_id = ?";

$result = $conn->execute_query($stmt, [
    $car_id,
    $owner_id
]);

if ($result->num_rows == 0) {
    die("This car doesn't exist.");
}

$car = $result->fetch_assoc();



// ======================================
// عند الضغط على زر Update
// ======================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $car_brand        = trim($_POST['car_brand'] ?? '');
    $car_model        = trim($_POST['car_model'] ?? '');
    $car_plate_number = trim($_POST['car_plate_number'] ?? '');
    $car_year         = (int)($_POST['car_year'] ?? 0);
    $car_color        = trim($_POST['car_color'] ?? '');
    $car_location     = trim($_POST['car_location'] ?? '');
    $car_mileage      = (int)($_POST['car_mileage'] ?? 0);
    $car_price        = (float)($_POST['car_price_per_day'] ?? 0);
    $car_fuel_type    = $_POST['car_fuel_type'] ?? '';
    $car_transmission = $_POST['car_transmission'] ?? '';
    $car_seats        = (int)($_POST['car_seats'] ?? 0);
    $car_description  = trim($_POST['car_description'] ?? '');



    // الصورة الحالية الموجودة فى قاعدة البيانات

    $car_image = $car['image'];



    // ===================================================
    // لو المالك اختار صورة جديدة
    // ===================================================

    if (!empty($_FILES['car_image']['name'])) {

        $newImageName = str_replace(" ", "_", $_FILES['car_image']['name']);

        $newImageTmp = $_FILES['car_image']['tmp_name'];

        $newTarget = "../images/CarOwner/" . basename($newImageName);



        // حذف الصورة القديمة

        if (file_exists($car['image'])) {

            unlink($car['image']);

        }



        // رفع الصورة الجديدة

        if (move_uploaded_file($newImageTmp, $newTarget)) {

            $car_image = $newTarget;

        }

    }
    // ============================================
    // التأكد أن رقم اللوحة غير مستخدم فى عربية تانية
    // ============================================

    $checkPlate = "
        SELECT id
        FROM cars
        WHERE plate_number = ?
        AND id != ?
    ";

    $check = $conn->execute_query($checkPlate, [
        $car_plate_number,
        $car_id
    ]);


    if ($check->num_rows > 0) {

        echo "<script>
                alert('This plate number already exists.');
              </script>";

    }
    else
    {

        // ======================================
        // تحديث بيانات العربية
        // ======================================

        $update = "
        UPDATE cars
        SET
        brand = ?,
        model = ?,
        plate_number = ?,
        year = ?,
        color = ?,
        location = ?,
        mileage = ?,
        price_per_day = ?,
        fuel_type = ?,
        transmission = ?,
        seats = ?,
        description = ?,
        image = ?
        WHERE id = ?
        AND owner_id = ?
    ";


        $conn->execute_query($update, [

        $car_brand,
        $car_model,
        $car_plate_number,
        $car_year,
        $car_color,
        $car_location,
        $car_mileage,
        $car_price,
        $car_fuel_type,
        $car_transmission,
        $car_seats,
        $car_description,
        $car_image,

        $car_id,
        $owner_id

    ]);


        header("Location: ShowOwnerCars.php");
        exit();

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Car</title>
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

    box-shadow:
    0 5px 20px rgba(0,0,0,0.1);

}



.icon {

    width: 90px;
    height: 60px;

    margin: auto;

    background: #fff3cd;

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



.current-image {

    text-align: center;

    margin-bottom: 20px;

}



.current-image img {

    width: 220px;

    height: 140px;

    object-fit: cover;

    border-radius: 12px;

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


<h1>Edit Car</h1>


<p class="subtitle">
Update your car information below.
</p>


<form method="POST" enctype="multipart/form-data">

    <label>Car Brand *</label>

    <input
        type="text"
        name="car_brand"
        value="<?= htmlspecialchars($car['brand']) ?>"
        required>


    <label>Car Model *</label>

    <input
        type="text"
        name="car_model"
        value="<?= htmlspecialchars($car['model']) ?>"
        required>


    <label>Plate Number *</label>

    <input
        type="text"
        name="car_plate_number"
        value="<?= htmlspecialchars($car['plate_number']) ?>"
        required>


    <div class="row">

        <div>

            <label>Model Year *</label>

            <input
                type="number"
                name="car_year"
                value="<?= htmlspecialchars($car['year']) ?>"
                min="1900"
                max="<?= date('Y') + 1 ?>"
                required>

        </div>


        <div>

            <label>Color *</label>

            <input
                type="text"
                name="car_color"
                value="<?= htmlspecialchars($car['color']) ?>"
                required>

        </div>

    </div>


    <label>Location *</label>

    <input
        type="text"
        name="car_location"
        value="<?= htmlspecialchars($car['location']) ?>"
        required>


    <div class="row">

        <div>

            <label>Mileage (KM) *</label>

            <input
                type="number"
                name="car_mileage"
                value="<?= (int)$car['mileage'] ?>"
                min="0"
                required>

        </div>


        <div>

            <label>Seats *</label>

            <input
                type="number"
                name="car_seats"
                value="<?= (int)$car['seats'] ?>"
                min="1"
                max="20"
                required>

        </div>

    </div>


    <label>Fuel Type *</label>

    <select name="car_fuel_type" required>

        <option value="petrol"
            <?= $car['fuel_type'] === 'petrol' ? 'selected' : '' ?>>
            Petrol
        </option>

        <option value="diesel"
            <?= $car['fuel_type'] === 'diesel' ? 'selected' : '' ?>>
            Diesel
        </option>

        <option value="electric"
            <?= $car['fuel_type'] === 'electric' ? 'selected' : '' ?>>
            Electric
        </option>

    </select>


    <label>Transmission *</label>

    <select name="car_transmission" required>

        <option value="automatic"
            <?= $car['transmission'] === 'automatic' ? 'selected' : '' ?>>
            Automatic
        </option>

        <option value="manual"
            <?= $car['transmission'] === 'manual' ? 'selected' : '' ?>>
            Manual
        </option>

    </select>


    <label>Price Per Day (EGP) *</label>

    <input
        type="number"
        name="car_price_per_day"
        value="<?= htmlspecialchars($car['price_per_day']) ?>"
        min="1"
        step="0.01"
        required>


    <label>Description</label>

    <textarea
        name="car_description"
        rows="4"><?= htmlspecialchars($car['description'] ?? '') ?></textarea>


    <label>Current Car Image</label>

    <div class="current-image">

        <img
            src="<?= htmlspecialchars($car['image']) ?>"
            alt="Car Image">

    </div>


    <label>Change Image (Optional)</label>

    <input
        type="file"
        name="car_image"
        class="file-input"
        accept="image/*">


    <button type="submit">
        Update Car
    </button>

</form>


</div>


</body>

</html>