<?php

require_once '../mysql/db_connect.php';


session_start();

if(!isset($_SESSION['owner_id'])){

    header("Location: auth/OwnerLogin.php");
    exit();

}

$owner_id = $_SESSION['owner_id'];

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

$result = $conn->execute_query($stmt,[

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

    $car_make         = trim($_POST['car_make']);
    $car_model        = trim($_POST['car_model']);
    $car_model_year   = $_POST['car_model_year'];
    $car_color        = trim($_POST['car_color']);
    $car_plate_number = trim($_POST['car_plate_number']);
    $car_daily_rent   = $_POST['car_daily_rent'];



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

    $checkPlate = "SELECT * FROM cars
                   WHERE plate_number = ?
                   AND id != ?
                   AND owner_id = ?";

$check = $conn->execute_query($checkPlate,[

    $car_plate_number,

    $car_id,

    $owner_id

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

        $update = "UPDATE cars
                   SET
                        make = ?,
                        model = ?,
                        model_year = ?,
                        color = ?,
                        plate_number = ?,
                        daily_rent = ?,
                        image = ?
                   WHERE
                        id = ?
                        AND owner_id = ?";


        $conn->execute_query($update,[
        
            $car_make,
            $car_model,
            $car_model_year,
            $car_color,
            $car_plate_number,
            $car_daily_rent,
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



input {

    width: 100%;

    height: 42px;

    border: 1px solid #ddd;

    border-radius: 8px;

    padding: 10px 15px;

    margin-bottom: 18px;

    font-size: 14px;

}



input:focus {

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
    <label>Car Make *</label>

<input 
type="text"
name="car_make"
value="<?= $car['make'] ?>"
required>



<label>Car Model *</label>

<input 
type="text"
name="car_model"
value="<?= $car['model'] ?>"
required>



<div class="row">

    <div>

        <label>Model Year *</label>

        <input 
        type="number"
        name="car_model_year"
        value="<?= $car['model_year'] ?>"
        min="1900"
        required>

    </div>



    <div>

        <label>Color *</label>

        <input 
        type="text"
        name="car_color"
        value="<?= $car['color'] ?>"
        required>

    </div>


</div>




<label>Plate Number *</label>

<input 
type="text"
name="car_plate_number"
value="<?= $car['plate_number'] ?>"
required>




<label>Daily Rent Price *</label>

<input 
type="number"
name="car_daily_rent"
value="<?= $car['daily_rent'] ?>"
min="0"
required>





<label>Current Car Image</label>


<div class="current-image">


<img src="<?= $car['image'] ?>" 
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