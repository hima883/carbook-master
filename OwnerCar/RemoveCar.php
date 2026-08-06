<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');

$user_id = $_SESSION['user_id'];

// =======================================
// Make sure logged-in user is an Owner
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
    die("You must be an owner to remove a car.");
}

$check_owner->close();

$owner_id = $user_id;


// =======================================
// التأكد من وجود رقم العربية
// =======================================

if (!isset($_GET['car_id'])) {

    die("Car ID Not Found");

}


$car_id = (int) $_GET['car_id'];



// =======================================
// جلب بيانات العربية
// =======================================

$query = "SELECT *
          FROM cars
          WHERE id = ?
          AND owner_id = ?";


$car = $conn->execute_query($query, [
    $car_id,
    $owner_id
]);


if ($car->num_rows == 0) {

    die("Car Not Found.");

}


$carData = $car->fetch_assoc();



// =======================================
// التأكد إن العربية ليست محجوزة
// =======================================

$bookingQuery = "
    SELECT id
    FROM bookings
    WHERE car_id = ?
    LIMIT 1
";

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

if (!empty($carData['image'])) {

    $imagePath = __DIR__ . '/../images/CarOwner/' . basename($carData['image']);

    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}


// =======================================
// حذف العربية من قاعدة البيانات
// =======================================

$deleteQuery = "DELETE FROM cars
                WHERE id = ?
                AND owner_id = ?";


$conn->execute_query($deleteQuery, [
    $car_id,
    $owner_id
]);

if ($conn->affected_rows !== 1) {
    die("Failed to delete car.");
}


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
