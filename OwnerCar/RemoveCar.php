<?php

require_once '../mysql/db_connect.php';


// =======================================
// التأكد من وجود رقم العربية
// =======================================

if (!isset($_GET['car_id'])) {

    die("Car ID Not Found");

}


$car_id = $_GET['car_id'];



// =======================================
// جلب بيانات العربية
// =======================================

$query = "SELECT *
          FROM cars
          WHERE id = ?
          AND owner_id = ?";


$car = $conn->execute_query($query, [

    $car_id,

    1        // استبدلها فيما بعد بـ $_SESSION['owner_id']

]);


if ($car->num_rows == 0) {

    die("Car Not Found.");

}


$carData = $car->fetch_assoc();



// =======================================
// التأكد إن العربية ليست محجوزة
// =======================================

$bookingQuery = "SELECT *
                 FROM bookings
                 WHERE car_id = ?";


$booking = $conn->execute_query($bookingQuery, [

    $car_id

]);


if ($booking->num_rows > 0) {

    echo "

    <script>

        alert('You cannot delete this car because it has bookings.');

        window.location='ShowOwnerCars.php';

    </script>

    ";

    exit();

}



// =======================================
// حذف صورة العربية من السيرفر
// =======================================

if (

    !empty($carData['image'])

    &&

    file_exists($carData['image'])

)

{

    unlink($carData['image']);

}


// =======================================
// حذف العربية من قاعدة البيانات
// =======================================

$deleteQuery = "DELETE FROM cars
                WHERE id = ?
                AND owner_id = ?";


$conn->execute_query($deleteQuery, [

    $car_id,

    1      // استبدلها بعدين بـ $_SESSION['owner_id']

]);




// =======================================
// رسالة نجاح ثم الرجوع لصفحة السيارات
// =======================================

echo "

<script>

alert('Car deleted successfully.');

window.location='ShowOwnerCars.php';

</script>

";

exit();

?>
